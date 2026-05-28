<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesAcademicManagement;
use App\Models\ClassSection;
use App\Models\Student;
use App\Models\User;
use App\Services\StudentBulkImportFileReader;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class StudentController extends Controller
{
    use AuthorizesAcademicManagement;

    public function index(Request $request): View
    {
        $this->ensureAcademicManager();

        $sections = ClassSection::query()
            ->with(['program', 'semester'])
            ->whereHas('program', fn ($q) => $q->whereIn('department_id', $this->manageableDepartmentIds()))
            ->orderBy('display_name')
            ->get();

        $query = Student::query()
            ->with(['user', 'classSection', 'program', 'semester'])
            ->whereHas('program', fn ($q) => $q->whereIn('department_id', $this->manageableDepartmentIds()));

        if ($request->filled('class_section_id')) {
            $section = ClassSection::findOrFail($request->integer('class_section_id'));
            $this->authorizeClassSection($section);
            $query->where('class_section_id', $section->id);
        }

        return view('academics.students.index', [
            'students' => $query->orderBy('enrollment_no')->get(),
            'sections' => $sections,
            'filterSectionId' => $request->integer('class_section_id') ?: null,
        ]);
    }

    public function create(): View
    {
        $this->ensureAcademicManager();

        return view('academics.students.create', $this->formData());
    }

    public function importCreate(Request $request): View
    {
        $this->ensureAcademicManager();

        return view('academics.students.import', [
            ...$this->formData(),
            'selectedSectionId' => $request->integer('class_section_id') ?: null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureAcademicManager();

        $validated = $this->validateStudent($request);
        $section = ClassSection::with('program', 'semester')->findOrFail($validated['class_section_id']);
        $this->authorizeClassSection($section);

        DB::transaction(function () use ($validated, $section) {
            $user = User::create([
                'name' => $validated['name'],
                'username' => $validated['username'],
                'email' => $validated['email'] ?? null,
                'password' => Hash::make($validated['password'] ?? 'student123'),
                'role' => 'student',
                'must_change_password' => true,
                'status' => 'active',
            ]);

            Student::create([
                'user_id' => $user->id,
                'program_id' => $section->program_id,
                'semester_id' => $section->semester_id,
                'class_section_id' => $section->id,
                'enrollment_no' => $validated['enrollment_no'],
                'roll_no' => $validated['roll_no'] ?? null,
                'mobile' => $validated['mobile'] ?? null,
                'status' => 'active',
            ]);
        });

        return redirect()
            ->route('academics.students.index', ['class_section_id' => $section->id])
            ->with('status', 'Student added.');
    }

    public function importStore(Request $request, StudentBulkImportFileReader $reader): RedirectResponse
    {
        $this->ensureAcademicManager();

        $validated = $request->validate([
            'class_section_id' => ['required', Rule::in($this->manageableSectionIds())],
            'student_file' => ['required', 'file'],
        ]);

        $section = ClassSection::with('program', 'semester')->findOrFail($validated['class_section_id']);
        $this->authorizeClassSection($section);

        $extension = strtolower($request->file('student_file')->getClientOriginalExtension() ?: '');
        if (! in_array($extension, ['csv', 'txt', 'xlsx'], true)) {
            throw ValidationException::withMessages([
                'student_file' => ['Upload a CSV or XLSX file.'],
            ]);
        }

        try {
            $rows = $reader->read($request->file('student_file'));
        } catch (\RuntimeException $e) {
            throw ValidationException::withMessages([
                'student_file' => [$e->getMessage()],
            ]);
        }

        $preparedRows = $this->prepareImportRows($rows);
        $errors = $this->validateImportRows($preparedRows);

        if ($errors !== []) {
            throw ValidationException::withMessages([
                'student_file' => $errors,
            ]);
        }

        DB::transaction(function () use ($preparedRows, $section) {
            foreach ($preparedRows as $row) {
                $user = User::create([
                    'name' => $row['name'],
                    'username' => $row['username'],
                    'email' => $row['email'],
                    'password' => Hash::make($row['password']),
                    'role' => 'student',
                    'must_change_password' => true,
                    'status' => 'active',
                ]);

                Student::create([
                    'user_id' => $user->id,
                    'program_id' => $section->program_id,
                    'semester_id' => $section->semester_id,
                    'class_section_id' => $section->id,
                    'enrollment_no' => $row['enrollment_no'],
                    'roll_no' => $row['roll_no'],
                    'mobile' => $row['mobile'],
                    'status' => 'active',
                ]);
            }
        });

        return redirect()
            ->route('academics.students.index', ['class_section_id' => $section->id])
            ->with('status', count($preparedRows).' student record(s) imported successfully.');
    }

    public function importTemplate(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $this->ensureAcademicManager();

        $headers = ['name', 'enrollment_no', 'roll_no', 'username', 'email', 'mobile', 'password'];
        $sample = ['Riya Patel', 'SU2026BCA006', '6', 'SU2026BCA006', 'riya.patel@example.com', '9876543210', 'student123'];

        return response()->streamDownload(function () use ($headers, $sample) {
            $handle = fopen('php://output', 'wb');
            fputcsv($handle, $headers);
            fputcsv($handle, $sample);
            fclose($handle);
        }, 'student-import-template.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function edit(Student $student): View
    {
        $this->ensureAcademicManager();
        $this->authorizeStudent($student);
        $student->load(['user', 'classSection', 'program', 'semester']);

        return view('academics.students.edit', [
            'student' => $student,
            ...$this->formData(),
        ]);
    }

    public function update(Request $request, Student $student): RedirectResponse
    {
        $this->ensureAcademicManager();
        $this->authorizeStudent($student);

        $validated = $this->validateStudent($request, $student);
        $section = ClassSection::with('program', 'semester')->findOrFail($validated['class_section_id']);
        $this->authorizeClassSection($section);

        DB::transaction(function () use ($validated, $student, $section) {
            $student->user->update([
                'name' => $validated['name'],
                'username' => $validated['username'],
                'email' => $validated['email'] ?? null,
                'status' => $validated['status'],
            ]);

            if (! empty($validated['password'])) {
                $student->user->update(['password' => Hash::make($validated['password'])]);
            }

            $student->update([
                'program_id' => $section->program_id,
                'semester_id' => $section->semester_id,
                'class_section_id' => $section->id,
                'enrollment_no' => $validated['enrollment_no'],
                'roll_no' => $validated['roll_no'] ?? null,
                'mobile' => $validated['mobile'] ?? null,
                'status' => $validated['status'],
            ]);
        });

        return redirect()
            ->route('academics.students.index', ['class_section_id' => $section->id])
            ->with('status', 'Student updated.');
    }

    public function destroy(Student $student): RedirectResponse
    {
        $this->ensureAcademicManager();
        $this->authorizeStudent($student);

        $sectionId = $student->class_section_id;

        if ($student->attendanceRecords()->exists()) {
            $student->update(['status' => 'inactive']);
            $student->user->update(['status' => 'inactive']);

            return redirect()
                ->route('academics.students.index', ['class_section_id' => $sectionId])
                ->with('status', 'Student has attendance history; marked inactive instead of deleted.');
        }

        DB::transaction(function () use ($student) {
            $user = $student->user;
            $student->delete();
            $user->delete();
        });

        return redirect()
            ->route('academics.students.index', ['class_section_id' => $sectionId])
            ->with('status', 'Student removed.');
    }

    protected function authorizeStudent(Student $student): void
    {
        $student->loadMissing('program');
        $this->authorizeProgram($student->program);
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        $sections = ClassSection::query()
            ->with(['program', 'semester'])
            ->where('status', 'active')
            ->whereHas('program', fn ($q) => $q->whereIn('department_id', $this->manageableDepartmentIds()))
            ->orderBy('display_name')
            ->get();

        return compact('sections');
    }

    /**
     * @return list<int>
     */
    private function manageableSectionIds(): array
    {
        return ClassSection::query()
            ->whereHas('program', fn ($q) => $q->whereIn('department_id', $this->manageableDepartmentIds()))
            ->pluck('id')
            ->all();
    }

    /**
     * @param list<array<string, string|null>> $rows
     * @return list<array<string, string|null>>
     */
    private function prepareImportRows(array $rows): array
    {
        return array_map(function (array $row) {
            $enrollmentNo = $this->normalizeImportValue($row['enrollment_no'] ?? null);
            $username = $this->normalizeImportValue($row['username'] ?? null) ?: $enrollmentNo;
            $password = $this->normalizeImportValue($row['password'] ?? null) ?: 'student123';

            return [
                'row_number' => (string) ($row['row_number'] ?? ''),
                'name' => $this->normalizeImportValue($row['name'] ?? null),
                'enrollment_no' => $enrollmentNo,
                'roll_no' => $this->normalizeImportValue($row['roll_no'] ?? null),
                'username' => $username,
                'email' => $this->normalizeImportValue($row['email'] ?? null),
                'mobile' => $this->normalizeImportValue($row['mobile'] ?? null),
                'password' => $password,
            ];
        }, $rows);
    }

    /**
     * @param list<array<string, string|null>> $rows
     * @return list<string>
     */
    private function validateImportRows(array $rows): array
    {
        $errors = [];
        $seenEnrollmentNos = [];
        $seenUsernames = [];
        $seenEmails = [];

        foreach ($rows as $row) {
            $validator = Validator::make($row, [
                'name' => ['required', 'string', 'max:255'],
                'enrollment_no' => ['required', 'string', 'max:64', 'unique:students,enrollment_no'],
                'roll_no' => ['nullable', 'string', 'max:32'],
                'username' => ['required', 'string', 'max:255', 'unique:users,username'],
                'email' => ['required', 'email', 'max:255', 'unique:users,email'],
                'mobile' => ['required', 'string', 'max:20'],
                'password' => ['required', 'string', 'min:8'],
            ]);

            $rowNumber = $row['row_number'] ?: '?';

            foreach ($validator->errors()->all() as $message) {
                $errors[] = 'Row '.$rowNumber.': '.$message;
            }

            $enrollmentKey = strtolower($row['enrollment_no'] ?? '');
            if ($enrollmentKey !== '') {
                if (isset($seenEnrollmentNos[$enrollmentKey])) {
                    $errors[] = 'Row '.$rowNumber.': enrollment number is duplicated in the upload file.';
                }

                $seenEnrollmentNos[$enrollmentKey] = true;
            }

            $usernameKey = strtolower($row['username'] ?? '');
            if ($usernameKey !== '') {
                if (isset($seenUsernames[$usernameKey])) {
                    $errors[] = 'Row '.$rowNumber.': username is duplicated in the upload file.';
                }

                $seenUsernames[$usernameKey] = true;
            }

            $emailKey = strtolower($row['email'] ?? '');
            if ($emailKey !== '') {
                if (isset($seenEmails[$emailKey])) {
                    $errors[] = 'Row '.$rowNumber.': email is duplicated in the upload file.';
                }

                $seenEmails[$emailKey] = true;
            }
        }

        return $errors;
    }

    private function normalizeImportValue(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    /**
     * @return array<string, mixed>
     */
    private function validateStudent(Request $request, ?Student $student = null): array
    {
        $sectionIds = $this->manageableSectionIds();

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => [
                'required',
                'string',
                'max:255',
                Rule::unique('users', 'username')->ignore($student?->user_id),
            ],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($student?->user_id),
            ],
            'enrollment_no' => [
                'required',
                'string',
                'max:64',
                Rule::unique('students', 'enrollment_no')->ignore($student?->id),
            ],
            'roll_no' => ['nullable', 'string', 'max:32'],
            'mobile' => ['nullable', 'string', 'max:20'],
            'class_section_id' => ['required', Rule::in($sectionIds)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'status' => [$student ? 'required' : 'nullable', Rule::in(['active', 'inactive'])],
        ]);
    }
}
