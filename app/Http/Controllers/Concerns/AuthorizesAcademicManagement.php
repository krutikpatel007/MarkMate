<?php

namespace App\Http\Controllers\Concerns;

use App\Models\ClassSection;
use App\Models\Department;
use App\Models\Program;
use App\Models\Semester;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait AuthorizesAcademicManagement
{
    protected function ensureAcademicManager(): void
    {
        abort_unless(Auth::user()->isAdmin() || Auth::user()->isHod(), 403);
    }

    /**
     * @return list<int>
     */
    protected function manageableDepartmentIds(): array
    {
        if (Auth::user()->isAdmin()) {
            return Department::query()->pluck('id')->all();
        }

        return Auth::user()
            ->facultyProfile()
            ->pluck('department_id')
            ->filter()
            ->values()
            ->all();
    }

    protected function programsQuery(): Builder
    {
        return Program::query()
            ->whereIn('department_id', $this->manageableDepartmentIds())
            ->orderBy('program_code');
    }

    protected function authorizeProgram(Program $program): void
    {
        abort_unless(in_array((int) $program->department_id, $this->manageableDepartmentIds(), true), 403);
    }

    protected function authorizeSemester(Semester $semester): void
    {
        $semester->loadMissing('program');
        $this->authorizeProgram($semester->program);
    }

    protected function authorizeClassSection(ClassSection $section): void
    {
        $section->loadMissing('program');
        $this->authorizeProgram($section->program);
    }

    protected function authorizeSubject(Subject $subject): void
    {
        $subject->loadMissing('program');
        $this->authorizeProgram($subject->program);
    }
}
