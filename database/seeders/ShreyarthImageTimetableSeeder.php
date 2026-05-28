<?php

namespace Database\Seeders;

use App\Models\ClassSection;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Subject;
use App\Models\SubjectAssignment;
use App\Models\Timetable;
use App\Models\User;
use App\Support\TimetablePeriods;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeds BCA Semester 1 Section A weekly slots from the reference Mon–Fri timetable image.
 */
class ShreyarthImageTimetableSeeder extends Seeder
{
    /**
     * @return array{Timetable, Timetable, Carbon}
     */
    public function run(
        ClassSection $section,
        Department $department,
        User $primaryFacultyUser,
        Faculty $primaryFaculty,
    ): array {
        $program = $section->program;
        $semester = $section->semester;

        $primaryFacultyUser->update(['name' => 'Maya Arora']);
        $primaryFaculty->update(['display_initials' => 'MA']);

        /** @var array<string, Faculty> */
        $facultyByIni = ['MA' => $primaryFaculty];

        $facultySpecs = [
            ['name' => 'Pallavi Khanna', 'username' => 'fac_pk', 'ini' => 'PK', 'code' => 'SCSA-FAC-PK'],
            ['name' => 'Prachi Patil', 'username' => 'fac_p', 'ini' => 'P', 'code' => 'SCSA-FAC-P'],
            ['name' => 'Neha Chopra', 'username' => 'fac_nc', 'ini' => 'NC', 'code' => 'SCSA-FAC-NC'],
            ['name' => 'Deeksha Rao', 'username' => 'fac_d', 'ini' => 'D', 'code' => 'SCSA-FAC-D'],
            ['name' => 'Devansh Patel', 'username' => 'fac_dp', 'ini' => 'DP', 'code' => 'SCSA-FAC-DP'],
            ['name' => 'Zara Khan', 'username' => 'fac_zk', 'ini' => 'ZK', 'code' => 'SCSA-FAC-ZK'],
        ];

        foreach ($facultySpecs as $spec) {
            $user = User::firstOrCreate(
                ['username' => $spec['username']],
                [
                    'name' => $spec['name'],
                    'email' => $spec['username'].'@scsa.local',
                    'password' => Hash::make('faculty123'),
                    'role' => 'faculty',
                    'must_change_password' => false,
                ]
            );
            if ($user->name !== $spec['name']) {
                $user->update(['name' => $spec['name']]);
            }

            $fac = Faculty::firstOrCreate(
                ['employee_code' => $spec['code']],
                [
                    'user_id' => $user->id,
                    'department_id' => $department->id,
                    'designation' => 'Assistant Professor',
                    'display_initials' => $spec['ini'],
                ]
            );
            $fac->update([
                'user_id' => $user->id,
                'department_id' => $department->id,
                'display_initials' => $spec['ini'],
            ]);
            $facultyByIni[$spec['ini']] = $fac;
        }

        $subjectDefs = [
            ['code' => 'APL', 'name' => 'Application Programming Lab'],
            ['code' => 'LA', 'name' => 'Liberal Arts / Language Activity'],
            ['code' => 'YOGA', 'name' => 'Yoga'],
            ['code' => 'DBMS', 'name' => 'Database Management Systems'],
            ['code' => 'WAD', 'name' => 'Web Application Development'],
            ['code' => 'SS', 'name' => 'Soft Skills'],
            ['code' => 'VD', 'name' => 'Visual Design'],
        ];

        $subjects = [];
        foreach ($subjectDefs as $def) {
            $subjects[$def['code']] = Subject::firstOrCreate(
                [
                    'semester_id' => $semester->id,
                    'subject_code' => $def['code'],
                ],
                [
                    'program_id' => $program->id,
                    'subject_name' => $def['name'],
                ]
            );
        }

        /** @var array<string, SubjectAssignment> */
        $pairs = [];

        $register = function (string $sub, string $ini) use (&$pairs, $section, $subjects, $facultyByIni) {
            $k = $sub.'|'.$ini;
            if (isset($pairs[$k])) {
                return $pairs[$k];
            }
            $pairs[$k] = SubjectAssignment::create([
                'faculty_id' => $facultyByIni[$ini]->id,
                'subject_id' => $subjects[$sub]->id,
                'class_section_id' => $section->id,
                'academic_year' => '2026-27',
            ]);

            return $pairs[$k];
        };

        $slots = [
            [1, '09:00:00', '09:55:00', 1, 'APL', 'MA', null],
            [1, '10:00:00', '10:55:00', 2, 'DBMS', 'NC', null],
            [1, '11:05:00', '13:00:00', 3, 'WAD', 'DP', 'WAD/DP/LAB-1'],
            [2, '09:00:00', '09:55:00', 1, 'LA', 'PK', null],
            [2, '10:00:00', '10:55:00', 2, 'DBMS', 'NC', null],
            [2, '11:05:00', '12:00:00', 3, 'DBMS', 'NC', null],
            [2, '12:05:00', '13:00:00', 4, 'WAD', 'D', null],
            [2, '13:40:00', '14:35:00', 5, 'APL', 'MA', null],
            [3, '09:00:00', '09:55:00', 1, 'YOGA', 'P', null],
            [3, '10:00:00', '10:55:00', 2, 'WAD', 'D', null],
            [3, '11:05:00', '12:00:00', 3, 'SS', 'ZK', null],
            [3, '12:05:00', '13:00:00', 4, 'LA', 'PK', null],
            [3, '14:40:00', '15:35:00', 5, 'DBMS', 'NC', 'DBMS/NC/LAB-3'],
            [4, '10:00:00', '10:55:00', 1, 'APL', 'MA', 'APL/LAB-2/MA'],
            [4, '11:05:00', '12:00:00', 2, 'SS', 'ZK', null],
            [4, '12:05:00', '13:00:00', 3, 'WAD', 'D', null],
            [4, '13:40:00', '14:35:00', 4, 'LA', 'PK', null],
            [5, '09:00:00', '09:55:00', 1, 'LA', 'PK', null],
            [5, '10:00:00', '10:55:00', 2, 'APL', 'MA', null],
            [5, '12:05:00', '13:00:00', 3, 'VD', 'DP', 'VD/LAB-1/DP'],
        ];

        foreach ($slots as [$dow, $start, $end, $lecNo, $sub, $ini, $cellLabel]) {
            $assignment = $register($sub, $ini);
            Timetable::create([
                'subject_assignment_id' => $assignment->id,
                'day_of_week' => $dow,
                'start_time' => $start,
                'end_time' => $end,
                'lecture_no' => $lecNo,
                'slot_type' => TimetablePeriods::inferSlotType($start, $end),
                'cell_label' => $cellLabel,
                'status' => 'active',
            ]);
        }

        $lectureDate = Carbon::today();
        if ($lectureDate->isWeekend()) {
            $lectureDate = $lectureDate->copy()->next(Carbon::MONDAY);
        }
        $dow = (int) $lectureDate->format('N');

        $daySlots = Timetable::query()
            ->whereHas('subjectAssignment', fn ($q) => $q->where('class_section_id', $section->id))
            ->where('day_of_week', $dow)
            ->orderBy('start_time')
            ->get();

        $first = $daySlots->get(0) ?? Timetable::query()->orderBy('id')->firstOrFail();
        $second = $daySlots->get(1) ?? $first;

        return [$first, $second, $lectureDate];
    }
}
