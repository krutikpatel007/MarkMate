<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Faculty;
use App\Models\User;
use App\Services\StaffBulkImportFileReader;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class StaffUserController extends Controller
{
    public function index(): View
    {
        $this->ensureManager();

        $roles = $this->manageableRoles();
        $query = User::query()
            ->with('facultyProfile.department')
            ->whereIn('role', $roles);

        if (Auth::user()->isHod()) {
            $query->whereHas('facultyProfile', function ($facultyQuery) {
                $facultyQuery->whereIn('department_id', $this->manageableDepartmentIds());
            });
        }

        return view('staff.index', [
            'staffUsers' => $query
                ->orderBy('role')
                ->orderBy('name')
                ->get(),
            'manageableRoles' => $roles,
        ]);
    }

    public function create(): View
    {
        $this->ensureManager();

        return view('staff.create', $this->formData());
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureManager();

        $validated = $this->validateStaff($request);

        DB::transaction(function () use ($validated) {
            $user = User::create([
                'name' => $validated['name'],
                'username' => $validated['username'],
                'email' => $validated['email'] ?? null,
                'password' => Hash::make($validated['password']),
                'role' => $validated['role'],
                'must_change_password' => true,
                'status' => 'active',
            ]);

            Faculty::create([
                'user_id' => $user->id,
                'department_id' => $validated['department_id'],
                'employee_code' => $validated['employee_code'],
                'designation' => $validated['designation'] ?? match ($validated['role']) {
                    'hod' => 'Head of Department',
                    'coe' => 'Controller of Examinations',
                    'admin_staff' => 'Admin Staff',
                    default => 'Faculty',
                },
                'display_initials' => $validated['display_initials'] ?? null,
                'status' => 'active',
            ]);
        });

        return redirect()->route('staff.index')->with('status', 'Staff user created successfully.');
    }

    public function edit(User $staff): View
    {
        $this->ensureCanManage($staff);

        return view('staff.edit', [
            'staff' => $staff->load('facultyProfile'),
            ...$this->formData(),
        ]);
    }

    public function update(Request $request, User $staff): RedirectResponse
    {
        $this->ensureCanManage($staff);

        $validated = $this->validateStaff($request, $staff);

        DB::transaction(function () use ($validated, $staff) {
            $staff->update([
                'name' => $validated['name'],
                'username' => $validated['username'],
                'email' => $validated['email'] ?? null,
                'role' => $validated['role'],
                'status' => $validated['status'],
                ...(($validated['password'] ?? null) ? [
                    'password' => Hash::make($validated['password']),
                    'must_change_password' => true,
                ] : []),
            ]);

            $staff->facultyProfile()->updateOrCreate(
                ['user_id' => $staff->id],
                [
                    'department_id' => $validated['department_id'],
                    'employee_code' => $validated['employee_code'],
                    'designation' => $validated['designation'] ?? match ($validated['role']) {
                        'hod' => 'Head of Department',
                        'coe' => 'Controller of Examinations',
                        'admin_staff' => 'Admin Staff',
                        default => 'Faculty',
                    },
                    'display_initials' => $validated['display_initials'] ?? null,
                    'status' => $validated['status'],
                ]
            );
        });

        return redirect()->route('staff.index')->with('status', 'Staff user updated successfully.');
    }

    public function status(Request $request, User $staff): RedirectResponse
    {
        $this->ensureCanManage($staff);

        abort_if($request->user()->is($staff), 422, 'You cannot remove your own account.');

        $validated = $request->validate([
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        DB::transaction(function () use ($staff, $validated) {
            $staff->update(['status' => $validated['status']]);
            $staff->facultyProfile?->update(['status' => $validated['status']]);

            if ($validated['status'] === 'inactive') {
                $staff->facultyProfile?->subjectAssignments()->update(['status' => 'inactive']);
            }
        });

        $message = $validated['status'] === 'active'
            ? 'Staff user reactivated successfully.'
            : 'Staff user removed from active use successfully.';

        return redirect()->route('staff.index')->with('status', $message);
    }

    private function ensureManager(): void
    {
        abort_unless(Auth::user()->isAdmin() || Auth::user()->isHod(), 403);
    }

    private function ensureCanManage(User $staff): void
    {
        $this->ensureManager();
        $staff->loadMissing('facultyProfile');

        abort_unless(in_array($staff->role, $this->manageableRoles(), true), 403);

        if (Auth::user()->isHod()) {
            abort_unless(
                $staff->facultyProfile !== null
                && in_array($staff->facultyProfile->department_id, $this->manageableDepartmentIds(), true),
                403
            );
        }
    }

    /**
     * @return list<string>
     */
    private function manageableRoles(): array
    {
        return Auth::user()->isAdmin() ? ['hod', 'faculty', 'coe', 'admin_staff'] : ['faculty'];
    }

    /**
     * @return list<int>
     */
    private function manageableDepartmentIds(): array
    {
        $user = Auth::user();
        if ($user->isAdmin()) {
            return Department::query()->pluck('id')->all();
        }

        // Central Exam Department has global visibility across all academic departments
        $userDeptCode = $user->facultyProfile?->department?->department_code;
        if ($userDeptCode === 'EXAM') {
            return Department::query()->pluck('id')->all();
        }

        return $user
            ->facultyProfile()
            ->pluck('department_id')
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        $departments = Department::query()
            ->where('status', 'active')
            ->whereIn('id', $this->manageableDepartmentIds())
            ->orderBy('department_name')
            ->get();

        return [
            'departments' => $departments,
            'manageableRoles' => $this->manageableRoles(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validateStaff(Request $request, ?User $staff = null): array
    {
        $facultyId = $staff?->facultyProfile?->id;

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => [
                'required',
                'string',
                'max:255',
                Rule::unique('users', 'username')->ignore($staff?->id),
            ],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($staff?->id),
            ],
            'password' => [
                $staff ? 'nullable' : 'required',
                'string',
                'min:8',
                'confirmed',
            ],
            'role' => ['required', Rule::in($this->manageableRoles())],
            'department_id' => [
                'required',
                Rule::exists('departments', 'id')->where(function ($query) {
                    $query->whereIn('id', $this->manageableDepartmentIds());
                }),
            ],
            'employee_code' => [
                'required',
                'string',
                'max:255',
                Rule::unique('faculty', 'employee_code')->ignore($facultyId),
            ],
            'designation' => ['nullable', 'string', 'max:255'],
            'display_initials' => ['nullable', 'string', 'max:12'],
            'status' => [$staff ? 'required' : 'nullable', Rule::in(['active', 'inactive'])],
        ]);
    }

    public function importCreate(Request $request): View
    {
        $this->ensureManager();

        return view('staff.import', [
            ...$this->formData(),
            'selectedDepartmentId' => $request->integer('department_id') ?: null,
        ]);
    }

    public function importStore(Request $request, StaffBulkImportFileReader $reader): RedirectResponse
    {
        $this->ensureManager();

        $validated = $request->validate([
            'department_id' => ['required', Rule::in($this->manageableDepartmentIds())],
            'staff_file' => ['required', 'file'],
        ]);

        $extension = strtolower($request->file('staff_file')->getClientOriginalExtension() ?: '');
        if (! in_array($extension, ['csv', 'txt', 'xlsx'], true)) {
            throw ValidationException::withMessages([
                'staff_file' => ['Upload a CSV or XLSX file.'],
            ]);
        }

        try {
            $rows = $reader->read($request->file('staff_file'));
        } catch (\RuntimeException $e) {
            throw ValidationException::withMessages([
                'staff_file' => [$e->getMessage()],
            ]);
        }

        $preparedRows = $this->prepareImportRows($rows);
        $errors = $this->validateImportRows($preparedRows);

        if ($errors !== []) {
            throw ValidationException::withMessages([
                'staff_file' => $errors,
            ]);
        }

        $departmentId = $validated['department_id'];

        DB::transaction(function () use ($preparedRows, $departmentId) {
            foreach ($preparedRows as $row) {
                $user = User::create([
                    'name' => $row['name'],
                    'username' => $row['username'],
                    'email' => $row['email'],
                    'password' => Hash::make($row['password']),
                    'role' => $row['role'],
                    'must_change_password' => true,
                    'status' => 'active',
                ]);

                Faculty::create([
                    'user_id' => $user->id,
                    'department_id' => $departmentId,
                    'employee_code' => $row['employee_code'],
                    'designation' => $row['designation'] ?? match ($row['role']) {
                        'hod' => 'Head of Department',
                        'coe' => 'Controller of Examinations',
                        'admin_staff' => 'Admin Staff',
                        default => 'Faculty',
                    },
                    'display_initials' => $row['display_initials'] ?? null,
                    'status' => 'active',
                ]);
            }
        });

        return redirect()
            ->route('staff.index')
            ->with('status', count($preparedRows).' staff record(s) imported successfully.');
    }

    public function importTemplate(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $this->ensureManager();

        $headers = ['name', 'username', 'email', 'role', 'employee_code', 'designation', 'display_initials', 'password'];
        $sample = ['Amit Patel', 'amit.patel', 'amit.patel@example.com', 'faculty', 'EMP001', 'Assistant Professor', 'AP', 'staff123'];

        return response()->streamDownload(function () use ($headers, $sample) {
            $handle = fopen('php://output', 'wb');
            fputcsv($handle, $headers);
            fputcsv($handle, $sample);
            fclose($handle);
        }, 'staff-import-template.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * @param list<array<string, string|null>> $rows
     * @return list<array<string, string|null>>
     */
    private function prepareImportRows(array $rows): array
    {
        return array_map(function (array $row) {
            $employeeCode = $this->normalizeImportValue($row['employee_code'] ?? null);
            $username = $this->normalizeImportValue($row['username'] ?? null) ?: $employeeCode;
            $password = $this->normalizeImportValue($row['password'] ?? null) ?: 'staff123';
            $role = strtolower($this->normalizeImportValue($row['role'] ?? null) ?? 'faculty');

            return [
                'row_number' => (string) ($row['row_number'] ?? ''),
                'name' => $this->normalizeImportValue($row['name'] ?? null),
                'username' => $username,
                'email' => $this->normalizeImportValue($row['email'] ?? null),
                'role' => $role,
                'employee_code' => $employeeCode,
                'designation' => $this->normalizeImportValue($row['designation'] ?? null),
                'display_initials' => $this->normalizeImportValue($row['display_initials'] ?? null),
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
        $seenEmployeeCodes = [];
        $seenUsernames = [];
        $seenEmails = [];

        foreach ($rows as $row) {
            $validator = Validator::make($row, [
                'name' => ['required', 'string', 'max:255'],
                'employee_code' => ['required', 'string', 'max:255', 'unique:faculty,employee_code'],
                'username' => ['required', 'string', 'max:255', 'unique:users,username'],
                'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
                'role' => ['required', Rule::in($this->manageableRoles())],
                'designation' => ['nullable', 'string', 'max:255'],
                'display_initials' => ['nullable', 'string', 'max:12'],
                'password' => ['required', 'string', 'min:8'],
            ]);

            $rowNumber = $row['row_number'] ?: '?';

            foreach ($validator->errors()->all() as $message) {
                $errors[] = 'Row '.$rowNumber.': '.$message;
            }

            $codeKey = strtolower($row['employee_code'] ?? '');
            if ($codeKey !== '') {
                if (isset($seenEmployeeCodes[$codeKey])) {
                    $errors[] = 'Row '.$rowNumber.': employee code is duplicated in the upload file.';
                }
                $seenEmployeeCodes[$codeKey] = true;
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
}
