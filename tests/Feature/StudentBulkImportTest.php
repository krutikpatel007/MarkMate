<?php

namespace Tests\Feature;

use App\Models\ClassSection;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class StudentBulkImportTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_hod_can_open_bulk_student_upload_page(): void
    {
        $this->withoutVite();

        $hod = User::where('username', 'hod')->firstOrFail();

        $this->actingAs($hod)
            ->get(route('academics.students.import.create'))
            ->assertOk()
            ->assertSeeText('Bulk upload students')
            ->assertSeeText('Download template');
    }

    public function test_hod_can_bulk_import_students_from_csv(): void
    {
        $hod = User::where('username', 'hod')->firstOrFail();
        $section = ClassSection::query()->orderBy('id')->firstOrFail();

        $file = UploadedFile::fake()->createWithContent('students.csv', implode("\n", [
            'name,enrollment_no,roll_no,username,email,mobile,password',
            'Kiran Joshi,SU2026BCA010,10,,kiran.joshi@example.com,9876500001,',
            'Mira Shah,SU2026BCA011,11,mira.shah,mira.shah@example.com,9876500002,temporary123',
        ]));

        $this->actingAs($hod)
            ->post(route('academics.students.import.store'), [
                'class_section_id' => $section->id,
                'student_file' => $file,
            ])
            ->assertRedirect(route('academics.students.index', ['class_section_id' => $section->id]));

        $this->assertDatabaseHas('users', [
            'name' => 'Kiran Joshi',
            'username' => 'SU2026BCA010',
            'role' => 'student',
            'must_change_password' => true,
        ]);

        $this->assertDatabaseHas('users', [
            'name' => 'Mira Shah',
            'username' => 'mira.shah',
            'role' => 'student',
        ]);

        $this->assertDatabaseHas('students', [
            'enrollment_no' => 'SU2026BCA010',
            'class_section_id' => $section->id,
            'roll_no' => '10',
            'mobile' => '9876500001',
        ]);

        $this->assertDatabaseHas('students', [
            'enrollment_no' => 'SU2026BCA011',
            'class_section_id' => $section->id,
            'roll_no' => '11',
            'mobile' => '9876500002',
        ]);
    }

    public function test_hod_can_bulk_import_students_from_xlsx(): void
    {
        $hod = User::where('username', 'hod')->firstOrFail();
        $section = ClassSection::query()->orderBy('id')->firstOrFail();

        $file = $this->makeInlineStringXlsxUpload([
            ['name', 'enrollment_no', 'roll_no', 'username', 'email', 'mobile', 'password'],
            ['Tina Bhatt', 'SU2026BCA012', '12', '', 'tina.bhatt@example.com', '9876500003', ''],
            ['Yash Modi', 'SU2026BCA013', '13', 'yash.modi', 'yash.modi@example.com', '9876500004', 'temporary123'],
        ]);

        $this->actingAs($hod)
            ->post(route('academics.students.import.store'), [
                'class_section_id' => $section->id,
                'student_file' => $file,
            ])
            ->assertRedirect(route('academics.students.index', ['class_section_id' => $section->id]));

        $this->assertDatabaseHas('users', [
            'name' => 'Tina Bhatt',
            'username' => 'SU2026BCA012',
            'role' => 'student',
        ]);

        $this->assertDatabaseHas('students', [
            'enrollment_no' => 'SU2026BCA013',
            'class_section_id' => $section->id,
            'roll_no' => '13',
        ]);
    }

    public function test_bulk_import_rejects_duplicate_rows_and_rolls_back_everything(): void
    {
        $hod = User::where('username', 'hod')->firstOrFail();
        $section = ClassSection::query()->orderBy('id')->firstOrFail();

        $file = UploadedFile::fake()->createWithContent('students.csv', implode("\n", [
            'name,enrollment_no,roll_no,username,email,mobile,password',
            'Aarohi Patel,SU2026BCA020,20,aarohi.patel,aarohi@example.com,9876500010,temporary123',
            'Devansh Mehta,SU2026BCA020,21,devansh.mehta,devansh@example.com,9876500011,temporary123',
        ]));

        $this->actingAs($hod)
            ->post(route('academics.students.import.store'), [
                'class_section_id' => $section->id,
                'student_file' => $file,
            ])
            ->assertSessionHasErrors('student_file');

        $this->assertDatabaseMissing('users', [
            'username' => 'aarohi.patel',
        ]);

        $this->assertDatabaseMissing('users', [
            'username' => 'devansh.mehta',
        ]);

        $this->assertSame(5, Student::count());
    }

    public function test_bulk_import_requires_email_but_mobile_is_optional(): void
    {
        $hod = User::where('username', 'hod')->firstOrFail();
        $section = ClassSection::query()->orderBy('id')->firstOrFail();

        // 1. Missing email should fail
        $fileMissingEmail = UploadedFile::fake()->createWithContent('students.csv', implode("\n", [
            'name,enrollment_no,roll_no,username,email,mobile,password',
            'Manav Shah,SU2026BCA030,30,manav.shah,,9876500030,temporary123',
        ]));

        $this->actingAs($hod)
            ->post(route('academics.students.import.store'), [
                'class_section_id' => $section->id,
                'student_file' => $fileMissingEmail,
            ])
            ->assertSessionHasErrors('student_file');

        $this->assertDatabaseMissing('users', [
            'username' => 'manav.shah',
        ]);

        // 2. Missing mobile should succeed
        $fileMissingMobile = UploadedFile::fake()->createWithContent('students.csv', implode("\n", [
            'name,enrollment_no,roll_no,username,email,mobile,password',
            'Pooja Dave,SU2026BCA031,31,pooja.dave,pooja.dave@example.com,,temporary123',
        ]));

        $this->actingAs($hod)
            ->post(route('academics.students.import.store'), [
                'class_section_id' => $section->id,
                'student_file' => $fileMissingMobile,
            ])
            ->assertRedirect(route('academics.students.index', ['class_section_id' => $section->id]));

        $this->assertDatabaseHas('students', [
            'enrollment_no' => 'SU2026BCA031',
            'mobile' => null,
        ]);
    }

    /**
     * @param list<list<string>> $rows
     */
    private function makeInlineStringXlsxUpload(array $rows): UploadedFile
    {
        $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.uniqid('students_', true).'.zip';
        @unlink($path);

        $archive = new \PharData($path);
        $archive['xl/worksheets/sheet1.xml'] = $this->buildWorksheetXml($rows);

        $content = file_get_contents($path);
        unset($archive);
        @unlink($path);

        return UploadedFile::fake()->createWithContent('students.xlsx', $content ?: '');
    }

    /**
     * @param list<list<string>> $rows
     */
    private function buildWorksheetXml(array $rows): string
    {
        $xmlRows = [];

        foreach ($rows as $rowIndex => $row) {
            $cells = [];

            foreach ($row as $columnIndex => $value) {
                $column = $this->excelColumnName($columnIndex + 1);
                $reference = $column.($rowIndex + 1);
                $escaped = htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
                $cells[] = '<c r="'.$reference.'" t="inlineStr"><is><t>'.$escaped.'</t></is></c>';
            }

            $xmlRows[] = '<row r="'.($rowIndex + 1).'">'.implode('', $cells).'</row>';
        }

        return '<?xml version="1.0" encoding="UTF-8"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<sheetData>'.implode('', $xmlRows).'</sheetData>'
            .'</worksheet>';
    }

    private function excelColumnName(int $index): string
    {
        $name = '';

        while ($index > 0) {
            $index--;
            $name = chr(65 + ($index % 26)).$name;
            $index = intdiv($index, 26);
        }

        return $name;
    }
}
