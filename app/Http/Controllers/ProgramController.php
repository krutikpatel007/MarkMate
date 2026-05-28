<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesAcademicManagement;
use App\Models\Department;
use App\Models\Program;
use App\Models\Semester;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProgramController extends Controller
{
    use AuthorizesAcademicManagement;

    public function index(): View
    {
        $this->ensureAcademicManager();

        $programs = Program::with('department')
            ->withCount(['semesters', 'classSections'])
            ->whereIn('department_id', $this->manageableDepartmentIds())
            ->orderBy('program_name')
            ->get();

        return view('programs.index', compact('programs'));
    }

    public function create(): View
    {
        $this->ensureAcademicManager();

        $departments = Department::where('status', 'active')
            ->whereIn('id', $this->manageableDepartmentIds())
            ->orderBy('department_name')
            ->get();

        return view('programs.create', compact('departments'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureAcademicManager();

        $validated = $request->validate([
            'department_id' => [
                'required',
                Rule::exists('departments', 'id'),
                Rule::in($this->manageableDepartmentIds())
            ],
            'program_code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('programs', 'program_code')->where('department_id', $request->input('department_id')),
            ],
            'program_name' => ['required', 'string', 'max:255'],
            'semester_count' => ['required', 'integer', 'min:1', 'max:16'],
        ]);

        $program = Program::create([
            'department_id' => $validated['department_id'],
            'program_code' => strtoupper($validated['program_code']),
            'program_name' => $validated['program_name'],
            'status' => 'active',
        ]);

        for ($i = 1; $i <= $validated['semester_count']; $i++) {
            Semester::create([
                'program_id' => $program->id,
                'semester_no' => $i,
                'semester_name' => 'Semester ' . $i,
            ]);
        }

        return redirect()->route('programs.index')->with('status', 'Program and semesters added.');
    }

    public function edit(Program $program): View
    {
        $this->ensureAcademicManager();
        $this->authorizeProgram($program);

        $departments = Department::where('status', 'active')
            ->whereIn('id', $this->manageableDepartmentIds())
            ->orderBy('department_name')
            ->get();

        return view('programs.edit', compact('program', 'departments'));
    }

    public function update(Request $request, Program $program): RedirectResponse
    {
        $this->ensureAcademicManager();
        $this->authorizeProgram($program);

        $validated = $request->validate([
            'department_id' => [
                'required',
                Rule::exists('departments', 'id'),
                Rule::in($this->manageableDepartmentIds())
            ],
            'program_code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('programs', 'program_code')
                    ->where('department_id', $request->input('department_id'))
                    ->ignore($program->id),
            ],
            'program_name' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $program->update([
            'department_id' => $validated['department_id'],
            'program_code' => strtoupper($validated['program_code']),
            'program_name' => $validated['program_name'],
            'status' => $validated['status'],
        ]);

        return redirect()->route('programs.index')->with('status', 'Program updated.');
    }

    public function destroy(Program $program): RedirectResponse
    {
        $this->ensureAcademicManager();
        $this->authorizeProgram($program);

        if ($program->classSections()->exists() || $program->subjects()->exists()) {
            return back()->withErrors([
                'program' => 'Cannot remove program while it has classes or subjects. Remove them first or set status to inactive.',
            ]);
        }

        $program->semesters()->delete();
        $program->delete();

        return redirect()->route('programs.index')->with('status', 'Program removed.');
    }
}
