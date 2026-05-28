<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Faculty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class StaffBulkImportTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_admin_and_hod_can_open_bulk_staff_upload_page(): void
    {
        $this->withoutVite();

        $admin = User::where('role', 'admin')->firstOrFail();
        $hod = User::where('role', 'hod')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('staff.import.create'))
            ->assertOk()
            ->assertSeeText('Bulk upload staff')
            ->assertSeeText('Download template');

        $this->actingAs($hod)
            ->get(route('staff.import.create'))
            ->assertOk()
            ->assertSeeText('Bulk upload staff')
            ->assertSeeText('Download template');
    }

    public function test_admin_can_bulk_import_staff_from_csv(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();
        $dept = Department::query()->orderBy('id')->firstOrFail();

        $file = UploadedFile::fake()->createWithContent('staff.csv', implode("\n", [
            'name,username,email,role,employee_code,designation,display_initials,password',
            'Vikram Shah,vikram.shah,vikram.shah@example.com,faculty,EMP101,Assistant Professor,VS,',
            'Neha Patel,neha.patel,neha.patel@example.com,hod,EMP102,Head,NP,temporary123',
        ]));

        $this->actingAs($admin)
            ->post(route('staff.import.store'), [
                'department_id' => $dept->id,
                'staff_file' => $file,
            ])
            ->assertRedirect(route('staff.index'));

        $this->assertDatabaseHas('users', [
            'name' => 'Vikram Shah',
            'username' => 'vikram.shah',
            'role' => 'faculty',
            'must_change_password' => true,
        ]);

        $this->assertDatabaseHas('users', [
            'name' => 'Neha Patel',
            'username' => 'neha.patel',
            'role' => 'hod',
        ]);

        $this->assertDatabaseHas('faculty', [
            'employee_code' => 'EMP101',
            'department_id' => $dept->id,
            'designation' => 'Assistant Professor',
            'display_initials' => 'VS',
        ]);

        $this->assertDatabaseHas('faculty', [
            'employee_code' => 'EMP102',
            'department_id' => $dept->id,
            'designation' => 'Head',
        ]);
    }

    public function test_admin_can_bulk_import_staff_from_xlsx(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();
        $dept = Department::query()->orderBy('id')->firstOrFail();

        $file = $this->makeInlineStringXlsxUpload([
            ['name', 'username', 'email', 'role', 'employee_code', 'designation', 'display_initials', 'password'],
            ['Rohit Sharma', 'rohit.sharma', 'rohit@example.com', 'faculty', 'EMP103', 'Lecturer', 'RS', ''],
            ['Pooja Hegde', 'pooja.hegde', 'pooja@example.com', 'faculty', 'EMP104', 'Professor', 'PH', 'temporary123'],
        ]);

        $this->actingAs($admin)
            ->post(route('staff.import.store'), [
                'department_id' => $dept->id,
                'staff_file' => $file,
            ])
            ->assertRedirect(route('staff.index'));

        $this->assertDatabaseHas('users', [
            'name' => 'Rohit Sharma',
            'username' => 'rohit.sharma',
            'role' => 'faculty',
        ]);

        $this->assertDatabaseHas('faculty', [
            'employee_code' => 'EMP104',
            'department_id' => $dept->id,
            'display_initials' => 'PH',
        ]);
    }

    public function test_bulk_import_rejects_duplicate_rows_and_rolls_back_everything(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();
        $dept = Department::query()->orderBy('id')->firstOrFail();
        $initialCount = User::count();

        $file = UploadedFile::fake()->createWithContent('staff.csv', implode("\n", [
            'name,username,email,role,employee_code,designation,display_initials,password',
            'Amit Trivedi,amit.trivedi,amit@example.com,faculty,EMP110,Lecturer,AT,temporary123',
            'Dev Patel,amit.trivedi,dev@example.com,faculty,EMP111,Lecturer,DP,temporary123',
        ]));

        $this->actingAs($admin)
            ->post(route('staff.import.store'), [
                'department_id' => $dept->id,
                'staff_file' => $file,
            ])
            ->assertSessionHasErrors('staff_file');

        $this->assertDatabaseMissing('users', [
            'name' => 'Amit Trivedi',
        ]);

        $this->assertDatabaseMissing('users', [
            'name' => 'Dev Patel',
        ]);

        $this->assertSame($initialCount, User::count());
    }

    public function test_bulk_import_rejects_invalid_roles_or_departments(): void
    {
        $hod = User::where('role', 'hod')->firstOrFail();
        $anotherDept = Department::create([
            'department_code' => 'OTHER',
            'department_name' => 'Other Department',
            'status' => 'active',
        ]);

        // HOD cannot import to a department they don't manage
        $file = UploadedFile::fake()->createWithContent('staff.csv', implode("\n", [
            'name,username,email,role,employee_code,designation,display_initials,password',
            'Vijay Iyer,vijay.iyer,vijay@example.com,faculty,EMP120,Lecturer,VI,temporary123',
        ]));

        $this->actingAs($hod)
            ->post(route('staff.import.store'), [
                'department_id' => $anotherDept->id,
                'staff_file' => $file,
            ])
            ->assertSessionHasErrors('department_id');

        // HOD cannot import staff with role HOD (they can only import faculty)
        $managedDeptId = $hod->facultyProfile->department_id;
        $fileWithHodRole = UploadedFile::fake()->createWithContent('staff.csv', implode("\n", [
            'name,username,email,role,employee_code,designation,display_initials,password',
            'Vijay Iyer,vijay.iyer,vijay@example.com,hod,EMP121,Head,VI,temporary123',
        ]));

        $this->actingAs($hod)
            ->post(route('staff.import.store'), [
                'department_id' => $managedDeptId,
                'staff_file' => $fileWithHodRole,
            ])
            ->assertSessionHasErrors('staff_file');
    }

    /**
     * @param list<list<string>> $rows
     */
    private function makeInlineStringXlsxUpload(array $rows): UploadedFile
    {
        $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.uniqid('staff_', true).'.zip';
        @unlink($path);

        $archive = new \PharData($path);
        $archive['xl/worksheets/sheet1.xml'] = $this->buildWorksheetXml($rows);

        $content = file_get_contents($path);
        unset($archive);
        @unlink($path);

        return UploadedFile::fake()->createWithContent('staff.xlsx', $content ?: '');
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
