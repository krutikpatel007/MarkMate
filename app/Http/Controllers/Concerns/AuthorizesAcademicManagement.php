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
        $user = Auth::user();
        $isExamDept = $user->isCoe() || $user->facultyProfile?->department?->department_code === 'EXAM';

        abort_unless(
            $user->isAdmin() 
            || $user->isHod() 
            || $user->isCoe() 
            || $user->isAdminStaff()
            || ($user->isFaculty() && $isExamDept), 
            403
        );
    }

    /**
     * @return list<int>
     */
    protected function manageableDepartmentIds(): array
    {
        $user = Auth::user();
        if ($user->isAdmin()) {
            return Department::query()->pluck('id')->all();
        }

        // Central Exam Department has global visibility across all academic departments
        $userDeptCode = $user->facultyProfile?->department?->department_code;
        if ($user->isCoe() || $user->isAdminStaff() || $userDeptCode === 'EXAM') {
            return Department::query()->pluck('id')->all();
        }

        return $user
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
