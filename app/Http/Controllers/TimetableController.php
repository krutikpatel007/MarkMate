<?php

namespace App\Http\Controllers;

use App\Models\ClassSection;
use App\Models\Faculty;
use App\Models\SubjectAssignment;
use App\Models\Timetable;
use App\Support\TimetableGridPresenter;
use App\Support\TimetablePeriods;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TimetableController extends Controller
{
    /** @var array<int, string> */
    private const DAY_NAMES = [
        1 => 'Monday',
        2 => 'Tuesday',
        3 => 'Wednesday',
        4 => 'Thursday',
        5 => 'Friday',
        6 => 'Saturday',
        7 => 'Sunday',
    ];

    public function index(Request $request): View
    {
        $this->ensureStaffOrFaculty();

        $authorizedDeptIds = $this->getAuthorizedDepartmentIds();

        $sections = ClassSection::query()
            ->with(['program', 'semester'])
            ->whereHas('program', function ($query) use ($authorizedDeptIds) {
                $query->whereIn('department_id', $authorizedDeptIds);
            })
            ->orderBy('display_name')
            ->get();

        $sectionId = $request->integer('class_section_id');
        $section = $sectionId
            ? $sections->firstWhere('id', $sectionId)
            : $sections->first();

        if ($sectionId && !$section) {
            abort(403, 'You are not authorized to view this class section timetable.');
        }

        $timetablesForSection = collect();
        if ($section) {
            $assignmentIds = SubjectAssignment::query()
                ->where('class_section_id', $section->id)
                ->pluck('id');

            $timetablesForSection = Timetable::query()
                ->whereIn('subject_assignment_id', $assignmentIds)
                ->where('status', 'active')
                ->with(['subjectAssignment.subject', 'subjectAssignment.faculty.user'])
                ->get();
        }

        $grid = TimetableGridPresenter::build($timetablesForSection);

        return view('timetables.index', [
            'sections' => $sections,
            'section' => $section,
            'university_name' => config('timetable.university_name'),
            'day_short_labels' => config('timetable.day_short_labels'),
            ...$grid,
        ]);
    }

    public function slots(): View
    {
        $this->ensureStaffOrFaculty();

        $authorizedDeptIds = $this->getAuthorizedDepartmentIds();

        $timetables = Timetable::query()
            ->with([
                'subjectAssignment.subject',
                'subjectAssignment.classSection',
                'subjectAssignment.faculty.user',
            ])
            ->whereHas('subjectAssignment.classSection.program', function ($query) use ($authorizedDeptIds) {
                $query->whereIn('department_id', $authorizedDeptIds);
            })
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        return view('timetables.slots', [
            'timetables' => $timetables,
            'dayNames' => self::DAY_NAMES,
        ]);
    }

    public function faculty(Request $request): View
    {
        $this->ensureStaffOrFaculty();

        $user = Auth::user();
        $authorizedDeptIds = $this->getAuthorizedDepartmentIds();

        if ($user->isFaculty()) {
            $selectedFaculty = $user->facultyProfile;
            $faculty = collect([$selectedFaculty]);
        } else {
            $faculty = Faculty::query()
                ->with('user')
                ->where('status', 'active')
                ->whereIn('department_id', $authorizedDeptIds)
                ->get()
                ->sortBy('user.name')
                ->values();

            $facultyId = $request->integer('faculty_id');
            $selectedFaculty = $facultyId
                ? $faculty->firstWhere('id', $facultyId)
                : $faculty->first();

            if ($facultyId && !$selectedFaculty) {
                abort(403, 'You are not authorized to view this faculty timetable.');
            }
        }

        $timetables = collect();
        if ($selectedFaculty) {
            $assignmentIds = SubjectAssignment::query()
                ->where('faculty_id', $selectedFaculty->id)
                ->pluck('id');

            $timetables = Timetable::query()
                ->whereIn('subject_assignment_id', $assignmentIds)
                ->where('status', 'active')
                ->with(['subjectAssignment.subject', 'subjectAssignment.classSection'])
                ->orderBy('day_of_week')
                ->orderBy('start_time')
                ->get();
        }

        return view('timetables.faculty', [
            'faculty' => $faculty,
            'selectedFaculty' => $selectedFaculty,
            'timetables' => $timetables,
            'dayNames' => self::DAY_NAMES,
        ]);
    }

    public function create(): View
    {
        $this->ensureManageable();

        return view('timetables.create', [
            'assignments' => $this->assignmentOptions(),
            'dayNames' => self::DAY_NAMES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureManageable();

        $validated = $this->validateTimetable($request);

        $assignment = SubjectAssignment::query()->findOrFail($validated['subject_assignment_id']);
        $authorizedDeptIds = $this->getAuthorizedDepartmentIds();
        $classDeptId = $assignment->classSection->program->department_id;

        abort_unless(in_array($classDeptId, $authorizedDeptIds), 403, 'You are not authorized to create timetable slots for this department.');

        Timetable::create($validated);

        return redirect()
            ->route('timetables.index', ['class_section_id' => $assignment->class_section_id])
            ->with('status', 'Timetable slot added.');
    }

    public function edit(Timetable $timetable): View
    {
        $this->ensureManageable();

        $timetable->load([
            'subjectAssignment.subject',
            'subjectAssignment.classSection.program',
            'subjectAssignment.faculty.user',
        ]);

        $authorizedDeptIds = $this->getAuthorizedDepartmentIds();
        $classDeptId = $timetable->subjectAssignment->classSection->program->department_id;

        abort_unless(in_array($classDeptId, $authorizedDeptIds), 403, 'You are not authorized to edit timetable slots for this department.');

        $assignments = $this->assignmentOptions();
        if (! $assignments->contains('id', $timetable->subject_assignment_id)) {
            $current = SubjectAssignment::query()
                ->with(['subject', 'classSection', 'faculty.user'])
                ->find($timetable->subject_assignment_id);
            if ($current) {
                $assignments = $assignments->prepend($current)->unique('id')->values();
            }
        }

        return view('timetables.edit', [
            'timetable' => $timetable,
            'assignments' => $assignments,
            'dayNames' => self::DAY_NAMES,
        ]);
    }

    public function update(Request $request, Timetable $timetable): RedirectResponse
    {
        $this->ensureManageable();

        $timetable->load('subjectAssignment.classSection.program');
        $authorizedDeptIds = $this->getAuthorizedDepartmentIds();
        $classDeptId = $timetable->subjectAssignment->classSection->program->department_id;

        abort_unless(in_array($classDeptId, $authorizedDeptIds), 403, 'You are not authorized to update timetable slots for this department.');

        $validated = $this->validateTimetable($request, $timetable->id);

        $assignment = SubjectAssignment::query()->findOrFail($validated['subject_assignment_id']);
        $newClassDeptId = $assignment->classSection->program->department_id;

        abort_unless(in_array($newClassDeptId, $authorizedDeptIds), 403, 'You are not authorized to update timetable slots to this department.');

        $timetable->update($validated);

        return redirect()
            ->route('timetables.index', ['class_section_id' => $assignment->class_section_id])
            ->with('status', 'Timetable slot updated.');
    }

    public function destroy(Timetable $timetable): RedirectResponse
    {
        $this->ensureManageable();

        $timetable->load('subjectAssignment.classSection.program');
        $authorizedDeptIds = $this->getAuthorizedDepartmentIds();
        $classDeptId = $timetable->subjectAssignment->classSection->program->department_id;

        abort_unless(in_array($classDeptId, $authorizedDeptIds), 403, 'You are not authorized to delete timetable slots for this department.');

        $sectionId = $timetable->subjectAssignment?->class_section_id;

        $timetable->delete();

        return redirect()
            ->route('timetables.index', array_filter(['class_section_id' => $sectionId]))
            ->with('status', 'Timetable slot removed.');
    }

    protected function getAuthorizedDepartmentIds(): array
    {
        $user = Auth::user();
        if ($user->isAdmin()) {
            return \App\Models\Department::pluck('id')->toArray();
        }
        if ($user->isHod() || $user->isFaculty()) {
            return [$user->facultyProfile->department_id];
        }
        return [];
    }

    private function ensureStaffOrFaculty(): void
    {
        abort_unless(
            Auth::user()->isAdmin() || 
            Auth::user()->isHod() || 
            Auth::user()->isFaculty(), 
            403
        );
    }

    private function ensureManageable(): void
    {
        abort_unless(Auth::user()->isAdmin() || Auth::user()->isHod(), 403);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, SubjectAssignment>
     */
    private function assignmentOptions()
    {
        $authorizedDeptIds = $this->getAuthorizedDepartmentIds();

        return SubjectAssignment::query()
            ->where('status', 'active')
            ->with(['subject', 'classSection.program', 'faculty.user'])
            ->whereHas('classSection.program', function ($query) use ($authorizedDeptIds) {
                $query->whereIn('department_id', $authorizedDeptIds);
            })
            ->orderBy('academic_year', 'desc')
            ->get();
    }

    /**
     * @return array<string, mixed>
     */
    private function validateTimetable(Request $request, ?int $ignoreId = null): array
    {
        $validated = $request->validate([
            'subject_assignment_id' => ['required', 'exists:subject_assignments,id'],
            'day_of_week' => ['required', 'integer', Rule::in([1, 2, 3, 4, 5, 6, 7])],
            'slot_type' => ['required', Rule::in(['regular', 'lab'])],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'lecture_no' => ['required', 'integer', 'min:1', 'max:20'],
            'cell_label' => ['nullable', 'string', 'max:64'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        if (strtotime($validated['start_time']) >= strtotime($validated['end_time'])) {
            throw ValidationException::withMessages([
                'end_time' => 'End time must be after start time.',
            ]);
        }

        $start = $validated['start_time'].':00';
        $end = $validated['end_time'].':00';

        if ($validated['slot_type'] === 'lab') {
            if (! TimetablePeriods::matchesLabPair($start, $end)) {
                throw ValidationException::withMessages([
                    'end_time' => 'Lab slots must use a full two-period block (e.g. 11:05–13:00). Pick a lab block from the list.',
                ]);
            }
        } elseif (! TimetablePeriods::matchesSinglePeriod($start, $end)) {
            throw ValidationException::withMessages([
                'end_time' => 'Regular lectures must match one standard 55-minute period. Pick a period from the list or switch type to Lab.',
            ]);
        }

        $this->assertNoSectionOverlap(
            (int) $validated['subject_assignment_id'],
            (int) $validated['day_of_week'],
            $start,
            $end,
            $ignoreId
        );

        $duplicate = Timetable::query()
            ->where('subject_assignment_id', $validated['subject_assignment_id'])
            ->where('day_of_week', $validated['day_of_week'])
            ->where('lecture_no', $validated['lecture_no'])
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'lecture_no' => 'This assignment already has lecture number '.$validated['lecture_no'].' on that day.',
            ]);
        }

        $validated['start_time'] = $start;
        $validated['end_time'] = $end;

        return $validated;
    }

    private function assertNoSectionOverlap(
        int $assignmentId,
        int $dayOfWeek,
        string $start,
        string $end,
        ?int $ignoreId = null,
    ): void {
        $sectionId = SubjectAssignment::query()->whereKey($assignmentId)->value('class_section_id');
        if (! $sectionId) {
            return;
        }

        $assignmentIds = SubjectAssignment::query()
            ->where('class_section_id', $sectionId)
            ->pluck('id');

        $conflict = Timetable::query()
            ->whereIn('subject_assignment_id', $assignmentIds)
            ->where('day_of_week', $dayOfWeek)
            ->where('status', 'active')
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->get()
            ->first(fn (Timetable $slot) => TimetablePeriods::timesOverlap(
                $start,
                $end,
                $slot->start_time,
                $slot->end_time
            ));

        if ($conflict) {
            throw ValidationException::withMessages([
                'start_time' => 'This time overlaps another slot on the same day for this class ('.TimetableGridPresenter::cellLabel($conflict).').',
            ]);
        }
    }
}
