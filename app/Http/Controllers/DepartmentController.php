<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesAcademicManagement;
use App\Models\Department;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class DepartmentController extends Controller
{
    use AuthorizesAcademicManagement;

    public function index(): View
    {
        abort_unless(Auth::user()->isAdmin(), 403);

        $departments = Department::withCount(['programs', 'faculty'])
            ->orderBy('department_name')
            ->get();

        return view('departments.index', compact('departments'));
    }

    public function create(): View
    {
        abort_unless(Auth::user()->isAdmin(), 403);

        return view('departments.create');
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(Auth::user()->isAdmin(), 403);

        $validated = $request->validate([
            'department_code' => ['required', 'string', 'max:20', Rule::unique('departments', 'department_code')],
            'department_name' => ['required', 'string', 'max:255'],
        ]);

        Department::create([
            'department_code' => strtoupper($validated['department_code']),
            'department_name' => $validated['department_name'],
            'status' => 'active',
        ]);

        return redirect()->route('departments.index')->with('status', 'Department added.');
    }

    public function edit(Department $department): View
    {
        abort_unless(Auth::user()->isAdmin(), 403);

        return view('departments.edit', compact('department'));
    }

    public function update(Request $request, Department $department): RedirectResponse
    {
        abort_unless(Auth::user()->isAdmin(), 403);

        $validated = $request->validate([
            'department_code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('departments', 'department_code')->ignore($department->id),
            ],
            'department_name' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $department->update([
            'department_code' => strtoupper($validated['department_code']),
            'department_name' => $validated['department_name'],
            'status' => $validated['status'],
        ]);

        return redirect()->route('departments.index')->with('status', 'Department updated.');
    }

    public function destroy(Department $department): RedirectResponse
    {
        abort_unless(Auth::user()->isAdmin(), 403);

        if ($department->programs()->exists()) {
            return back()->withErrors([
                'department' => 'Cannot remove department while it has programs. Remove programs first or set status to inactive.',
            ]);
        }

        if ($department->faculty()->exists()) {
            $department->update(['status' => 'inactive']);
            return redirect()->route('departments.index')->with('status', 'Department has faculty members; marked inactive instead of deleted.');
        }

        $department->delete();

        return redirect()->route('departments.index')->with('status', 'Department removed.');
    }

    public function togglePastAttendance(Request $request): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user->isAdmin() || $user->isHod(), 403);

        $validated = $request->validate([
            'department_id' => ['required', 'exists:departments,id'],
            'allow_past_attendance' => ['required', 'boolean'],
        ]);

        if ($user->isHod()) {
            abort_unless(in_array((int)$validated['department_id'], $this->manageableDepartmentIds(), true), 403);
        }

        $department = Department::findOrFail($validated['department_id']);
        $department->update([
            'allow_past_attendance' => $validated['allow_past_attendance'],
        ]);

        return back()->with('status', 'Past attendance permission updated successfully.');
    }
}
