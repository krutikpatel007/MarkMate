<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesAcademicManagement;
use App\Models\ClassSection;
use App\Models\Semester;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ClassSectionController extends Controller
{
    use AuthorizesAcademicManagement;

    public function index(): View
    {
        $this->ensureAcademicManager();

        $sections = ClassSection::query()
            ->with(['program', 'semester'])
            ->withCount('students')
            ->whereHas('program', fn ($q) => $q->whereIn('department_id', $this->manageableDepartmentIds()))
            ->orderBy('display_name')
            ->get();

        return view('academics.classes.index', compact('sections'));
    }

    public function create(): View
    {
        $this->ensureAcademicManager();

        return view('academics.classes.create', $this->formData());
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureAcademicManager();

        $validated = $this->validateSection($request);
        $semester = Semester::with('program')->findOrFail($validated['semester_id']);
        $this->authorizeSemester($semester);

        abort_unless((int) $semester->program_id === (int) $validated['program_id'], 422, 'Semester does not belong to the selected program.');

        $displayName = $validated['display_name']
            ?: "{$semester->program->program_name} Sem {$semester->semester_no} {$validated['section_name']}";

        ClassSection::create([
            'program_id' => $validated['program_id'],
            'semester_id' => $validated['semester_id'],
            'section_name' => strtoupper($validated['section_name']),
            'display_name' => $displayName,
            'status' => 'active',
        ]);

        return redirect()->route('academics.classes.index')->with('status', 'Class section added.');
    }

    public function edit(ClassSection $classSection): View
    {
        $this->ensureAcademicManager();
        $this->authorizeClassSection($classSection);
        $classSection->load(['program', 'semester']);

        return view('academics.classes.edit', [
            'section' => $classSection,
            ...$this->formData(),
        ]);
    }

    public function update(Request $request, ClassSection $classSection): RedirectResponse
    {
        $this->ensureAcademicManager();
        $this->authorizeClassSection($classSection);

        $validated = $this->validateSection($request, $classSection);
        $semester = Semester::with('program')->findOrFail($validated['semester_id']);
        $this->authorizeSemester($semester);

        abort_unless((int) $semester->program_id === (int) $validated['program_id'], 422, 'Semester does not belong to the selected program.');

        $displayName = $validated['display_name']
            ?: "{$semester->program->program_name} Sem {$semester->semester_no} {$validated['section_name']}";

        $classSection->update([
            'program_id' => $validated['program_id'],
            'semester_id' => $validated['semester_id'],
            'section_name' => strtoupper($validated['section_name']),
            'display_name' => $displayName,
            'status' => $validated['status'],
        ]);

        return redirect()->route('academics.classes.index')->with('status', 'Class section updated.');
    }

    public function destroy(ClassSection $classSection): RedirectResponse
    {
        $this->ensureAcademicManager();
        $this->authorizeClassSection($classSection);

        if ($classSection->students()->exists()) {
            return back()->withErrors([
                'section' => 'Cannot remove this class while students are enrolled. Move or remove students first, or set status to inactive.',
            ]);
        }

        if ($classSection->subjectAssignments()->exists()) {
            $classSection->update(['status' => 'inactive']);

            return redirect()->route('academics.classes.index')->with('status', 'Class has faculty assignments; marked inactive instead of deleted.');
        }

        $classSection->delete();

        return redirect()->route('academics.classes.index')->with('status', 'Class section removed.');
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
    private function validateSection(Request $request, ?ClassSection $section = null): array
    {
        $programIds = $this->programsQuery()->pluck('id')->all();

        return $request->validate([
            'program_id' => ['required', Rule::in($programIds)],
            'semester_id' => [
                'required',
                Rule::exists('semesters', 'id')->where(fn ($q) => $q->where('program_id', $request->input('program_id'))),
            ],
            'section_name' => [
                'required',
                'string',
                'max:20',
                Rule::unique('class_sections', 'section_name')
                    ->where('semester_id', $request->input('semester_id'))
                    ->ignore($section?->id),
            ],
            'display_name' => ['nullable', 'string', 'max:255'],
            'status' => [$section ? 'required' : 'nullable', Rule::in(['active', 'inactive'])],
        ]);
    }
}
