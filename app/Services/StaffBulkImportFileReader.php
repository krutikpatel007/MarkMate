<?php

namespace App\Services;

use DOMDocument;
use DOMXPath;
use Illuminate\Http\UploadedFile;
use PharData;
use RuntimeException;

class StaffBulkImportFileReader
{
    /**
     * @return list<array<string, string|null>>
     */
    public function read(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: '');

        return match ($extension) {
            'csv', 'txt' => $this->readCsv($file),
            'xlsx' => $this->readXlsx($file),
            default => throw new RuntimeException('Upload a CSV or XLSX file.'),
        };
    }

    /**
     * @return list<array<string, string|null>>
     */
    private function readCsv(UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'rb');

        if ($handle === false) {
            throw new RuntimeException('The uploaded file could not be opened.');
        }

        $headers = null;
        $rows = [];
        $rowNumber = 0;

        while (($data = fgetcsv($handle)) !== false) {
            $rowNumber++;
            $data = array_map(fn ($value) => is_string($value) ? trim($this->stripUtf8Bom($value)) : '', $data);

            if ($headers === null) {
                $headers = $this->normalizeHeaders($data);
                continue;
            }

            if ($this->rowIsBlank($data)) {
                continue;
            }

            $rows[] = $this->combineRow($headers, $data, $rowNumber);
        }

        fclose($handle);

        return $this->finalizeRows($headers, $rows);
    }

    /**
     * @return list<array<string, string|null>>
     */
    private function readXlsx(UploadedFile $file): array
    {
        $tempPath = sys_get_temp_dir().DIRECTORY_SEPARATOR.uniqid('staff_', true).'.zip';

        if (! copy($file->getRealPath(), $tempPath)) {
            throw new RuntimeException('The uploaded spreadsheet could not be prepared.');
        }

        try {
            $archive = new PharData($tempPath);

            if (! isset($archive['xl/worksheets/sheet1.xml'])) {
                throw new RuntimeException('The uploaded XLSX file does not contain a readable first worksheet.');
            }

            $sharedStrings = isset($archive['xl/sharedStrings.xml'])
                ? $this->readSharedStrings($archive['xl/sharedStrings.xml']->getContent())
                : [];

            return $this->readWorksheetXml($archive['xl/worksheets/sheet1.xml']->getContent(), $sharedStrings);
        } catch (\Throwable $e) {
            throw new RuntimeException('The uploaded XLSX file could not be read.', 0, $e);
        } finally {
            @unlink($tempPath);
        }
    }

    /**
     * @return list<string>
     */
    private function readSharedStrings(string $xml): array
    {
        $document = new DOMDocument();
        $document->loadXML($xml);

        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('main', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');

        $strings = [];

        foreach ($xpath->query('//main:si') as $item) {
            $value = '';

            foreach ($xpath->query('.//main:t', $item) as $textNode) {
                $value .= $textNode->textContent;
            }

            $strings[] = trim($value);
        }

        return $strings;
    }

    /**
     * @param list<string> $sharedStrings
     * @return list<array<string, string|null>>
     */
    private function readWorksheetXml(string $xml, array $sharedStrings): array
    {
        $document = new DOMDocument();
        $document->loadXML($xml);

        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('main', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');

        $headers = null;
        $rows = [];

        foreach ($xpath->query('//main:sheetData/main:row') as $rowNode) {
            $rowNumber = (int) $rowNode->attributes?->getNamedItem('r')?->nodeValue ?: 0;
            $valuesByColumn = [];
            $maxColumn = 0;

            foreach ($xpath->query('main:c', $rowNode) as $cellNode) {
                $reference = $cellNode->attributes?->getNamedItem('r')?->nodeValue ?? '';
                $columnIndex = $this->columnIndexFromReference($reference);
                $maxColumn = max($maxColumn, $columnIndex);
                $valuesByColumn[$columnIndex] = $this->extractCellValue($xpath, $cellNode, $sharedStrings);
            }

            $orderedValues = [];
            for ($column = 1; $column <= $maxColumn; $column++) {
                $orderedValues[] = trim($valuesByColumn[$column] ?? '');
            }

            if ($headers === null) {
                $headers = $this->normalizeHeaders($orderedValues);
                continue;
            }

            if ($this->rowIsBlank($orderedValues)) {
                continue;
            }

            $rows[] = $this->combineRow($headers, $orderedValues, $rowNumber > 0 ? $rowNumber : count($rows) + 2);
        }

        return $this->finalizeRows($headers, $rows);
    }

    /**
     * @param list<string> $sharedStrings
     */
    private function extractCellValue(DOMXPath $xpath, \DOMNode $cellNode, array $sharedStrings): string
    {
        $type = $cellNode->attributes?->getNamedItem('t')?->nodeValue ?? '';

        if ($type === 'inlineStr') {
            $value = '';

            foreach ($xpath->query('main:is/main:t', $cellNode) as $textNode) {
                $value .= $textNode->textContent;
            }

            return $value;
        }

        $valueNode = $xpath->query('main:v', $cellNode)->item(0);

        if ($valueNode === null) {
            return '';
        }

        $value = $valueNode->textContent;

        if ($type === 's') {
            return $sharedStrings[(int) $value] ?? '';
        }

        if ($type === '' || $type === 'n') {
            if (is_numeric($value)) {
                $floatVal = (float) $value;
                if ($floatVal == (int) $floatVal) {
                    return (string) (int) $floatVal;
                }
                if (stripos($value, 'e') !== false) {
                    return sprintf('%f', $floatVal);
                }
            }
        }

        return $value;
    }

    private function columnIndexFromReference(string $reference): int
    {
        $letters = preg_replace('/[^A-Z]/', '', strtoupper($reference)) ?: '';
        $index = 0;

        foreach (str_split($letters) as $letter) {
            $index = ($index * 26) + (ord($letter) - 64);
        }

        return max(1, $index);
    }

    /**
     * @param array<int, string> $headers
     * @param array<int, string> $values
     * @return array<string, string|null>
     */
    private function combineRow(array $headers, array $values, int $rowNumber): array
    {
        $row = ['row_number' => (string) $rowNumber];

        foreach ($headers as $index => $header) {
            if ($header === '') {
                continue;
            }

            $value = isset($values[$index]) ? trim((string) $values[$index]) : '';
            $row[$header] = $value === '' ? null : $value;
        }

        return $row;
    }

    /**
     * @param array<int, string>|null $headers
     * @param list<array<string, string|null>> $rows
     * @return list<array<string, string|null>>
     */
    private function finalizeRows(?array $headers, array $rows): array
    {
        if ($headers === null) {
            throw new RuntimeException('The uploaded file is empty.');
        }

        $required = ['name', 'username', 'role', 'employee_code'];
        $missing = array_values(array_filter($required, fn ($header) => ! in_array($header, $headers, true)));

        if ($missing !== []) {
            throw new RuntimeException('Missing required column(s): '.implode(', ', $missing).'.');
        }

        if ($rows === []) {
            throw new RuntimeException('The uploaded file does not contain any staff rows.');
        }

        return $rows;
    }

    /**
     * @param array<int, string> $headers
     * @return array<int, string>
     */
    private function normalizeHeaders(array $headers): array
    {
        return array_map(function ($header) {
            $normalized = strtolower(trim((string) $header));
            $normalized = preg_replace('/[^a-z0-9]+/', '_', $normalized) ?? '';
            $normalized = trim($normalized, '_');

            return match ($normalized) {
                'full_name', 'staff_name', 'name' => 'name',
                'user_name', 'username' => 'username',
                'email_address' => 'email',
                'role', 'staff_role' => 'role',
                'employee_code', 'emp_code', 'code' => 'employee_code',
                'designation', 'job_title', 'title' => 'designation',
                'display_initials', 'initials' => 'display_initials',
                'initial_password', 'login_password', 'password' => 'password',
                default => $normalized,
            };
        }, $headers);
    }

    /**
     * @param array<int, string> $values
     */
    private function rowIsBlank(array $values): bool
    {
        foreach ($values as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    private function stripUtf8Bom(string $value): string
    {
        return str_starts_with($value, "\xEF\xBB\xBF") ? substr($value, 3) : $value;
    }
}
