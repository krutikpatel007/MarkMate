<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesAcademicManagement;
use App\Models\Semester;
use App\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SubjectController extends Controller
{
    use AuthorizesAcademicManagement;

    public function index(): View
    {
        $this->ensureAcademicManager();

        $subjects = Subject::query()
            ->with(['program', 'semester'])
            ->whereHas('program', fn ($q) => $q->whereIn('department_id', $this->manageableDepartmentIds()))
            ->orderBy('subject_code')
            ->get();

        return view('academics.subjects.index', compact('subjects'));
    }

    public function create(): View
    {
        $this->ensureAcademicManager();

        return view('academics.subjects.create', $this->formData());
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureAcademicManager();

        $validated = $this->validateSubject($request);
        $semester = Semester::with('program')->findOrFail($validated['semester_id']);
        $this->authorizeSemester($semester);

        abort_unless((int) $semester->program_id === (int) $validated['program_id'], 422);

        Subject::create([
            'program_id' => $validated['program_id'],
            'semester_id' => $validated['semester_id'],
            'subject_code' => strtoupper($validated['subject_code']),
            'subject_name' => $validated['subject_name'],
            'status' => 'active',
        ]);

        return redirect()->route('academics.subjects.index')->with('status', 'Subject added.');
    }

    public function edit(Subject $subject): View
    {
        $this->ensureAcademicManager();
        $this->authorizeSubject($subject);
        $subject->load(['program', 'semester']);

        return view('academics.subjects.edit', [
            'subject' => $subject,
            ...$this->formData(),
        ]);
    }

    public function update(Request $request, Subject $subject): RedirectResponse
    {
        $this->ensureAcademicManager();
        $this->authorizeSubject($subject);

        $validated = $this->validateSubject($request, $subject);
        $semester = Semester::with('program')->findOrFail($validated['semester_id']);
        $this->authorizeSemester($semester);

        abort_unless((int) $semester->program_id === (int) $validated['program_id'], 422);

        $subject->update([
            'program_id' => $validated['program_id'],
            'semester_id' => $validated['semester_id'],
            'subject_code' => strtoupper($validated['subject_code']),
            'subject_name' => $validated['subject_name'],
            'status' => $validated['status'],
        ]);

        return redirect()->route('academics.subjects.index')->with('status', 'Subject updated.');
    }

    public function destroy(Subject $subject): RedirectResponse
    {
        $this->ensureAcademicManager();
        $this->authorizeSubject($subject);

        if ($subject->subjectAssignments()->exists()) {
            $subject->update(['status' => 'inactive']);

            return redirect()->route('academics.subjects.index')->with('status', 'Subject has class assignments; marked inactive instead of deleted.');
        }

        $subject->delete();

        return redirect()->route('academics.subjects.index')->with('status', 'Subject removed.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        $programs = $this->programsQuery()->with(['semesters' => fn ($q) => $q->orderBy('semester_no')])->get();

        return compact('programs');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateSubject(Request $request, ?Subject $subject = null): array
    {
        $programIds = $this->programsQuery()->pluck('id')->all();

        return $request->validate([
            'program_id' => ['required', Rule::in($programIds)],
            'semester_id' => [
                'required',
                Rule::exists('semesters', 'id')->where(fn ($q) => $q->where('program_id', $request->input('program_id'))),
            ],
            'subject_code' => [
                'required',
                'string',
                'max:32',
                Rule::unique('subjects', 'subject_code')
                    ->where('semester_id', $request->input('semester_id'))
                    ->ignore($subject?->id),
            ],
            'subject_name' => ['required', 'string', 'max:255'],
            'status' => [$subject ? 'required' : 'nullable', Rule::in(['active', 'inactive'])],
        ]);
    }
}
