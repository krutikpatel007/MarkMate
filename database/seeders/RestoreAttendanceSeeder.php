<?php

namespace Database\Seeders;

use App\Models\AttendanceRecord;
use App\Models\Faculty;
use App\Models\LectureSession;
use App\Models\Student;
use App\Models\SubjectAssignment;
use App\Models\Timetable;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * RestoreAttendanceSeeder
 *
 * Generates realistic lecture sessions and attendance records for all active
 * subject assignments using their real timetable slots.
 * Covers the last 4 weeks of working days.
 *
 * Attendance patterns:
 *   - 1 student (Riya Patel)      → ~20%  attendance (chronic absentee)
 *   - 1 student (Aarav Mehta)     → ~60%  attendance (average)
 *   - 3 students (rest)           → ~90%+ attendance (regular)
 */
class RestoreAttendanceSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Clear existing lecture sessions & attendance (fresh restore)
        AttendanceRecord::query()->delete();
        LectureSession::query()->delete();

        $this->command->info('Cleared old lecture sessions and attendance records.');

        $students = Student::with('user')->get();

        if ($students->isEmpty()) {
            $this->command->error('No students found. Run DatabaseSeeder first.');
            return;
        }

        // Define attendance profiles per student index (0-based)
        // 0 = chronic absentee (~20%), 1 = average (~60%), 2-4 = regular (~90%)
        $attendanceProfiles = [
            0 => 0.20,  // Riya Patel
            1 => 0.60,  // Aarav Mehta
            2 => 0.92,  // Nisha Trivedi
            3 => 0.88,  // Dev Shah
            4 => 0.95,  // Krisha Joshi
        ];

        // Build list of working days for the last 4 weeks (Mon–Sat)
        $workingDays = [];
        $start = Carbon::now()->subWeeks(4)->startOfWeek(Carbon::MONDAY);
        $end = Carbon::now()->subDay();

        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            // Skip Sunday (0)
            if ($date->dayOfWeek === Carbon::SUNDAY) {
                continue;
            }
            $workingDays[] = $date->copy();
        }

        // day_of_week mapping: timetable uses 1=Mon, 2=Tue, 3=Wed, 4=Thu, 5=Fri, 6=Sat
        // Carbon uses: 1=Mon, 2=Tue, 3=Wed, 4=Thu, 5=Fri, 6=Sat, 0=Sun
        // Map Carbon dayOfWeek → timetable day_of_week
        $carbonToTimetableDay = [
            1 => 1, // Monday
            2 => 2, // Tuesday
            3 => 3, // Wednesday
            4 => 4, // Thursday
            5 => 5, // Friday
            6 => 6, // Saturday
        ];

        $totalSessions = 0;
        $totalAttendance = 0;

        // Get all active subject assignments with their timetables
        $assignments = SubjectAssignment::where('status', 'active')
            ->with(['timetables', 'faculty.user', 'subject', 'classSection'])
            ->get();

        foreach ($assignments as $assignment) {
            $timetables = $assignment->timetables;

            if ($timetables->isEmpty()) {
                $this->command->warn("  No timetable for: {$assignment->subject->subject_code}");
                continue;
            }

            $facultyUserId = $assignment->faculty->user_id;

            // Get students in this class section
            $sectionStudents = $students->where('class_section_id', $assignment->class_section_id)->values();

            if ($sectionStudents->isEmpty()) {
                continue;
            }

            // For each working day, check if this subject has a timetable slot
            foreach ($workingDays as $date) {
                $timetableDay = $carbonToTimetableDay[$date->dayOfWeek] ?? null;
                if (!$timetableDay) continue;

                $daySlots = $timetables->where('day_of_week', $timetableDay);

                foreach ($daySlots as $slot) {
                    // Create lecture session
                    $session = LectureSession::create([
                        'timetable_id'          => $slot->id,
                        'subject_assignment_id' => $assignment->id,
                        'lecture_date'          => $date->toDateString(),
                        'start_time'            => $slot->start_time,
                        'end_time'              => $slot->end_time,
                        'lecture_no'            => $slot->lecture_no,
                        'session_type'          => 'regular',
                        'status'                => 'conducted',
                        'submitted_at'          => $date->copy()->setTimeFromTimeString($slot->end_time),
                    ]);

                    $totalSessions++;

                    // Record attendance for each student
                    foreach ($sectionStudents as $index => $student) {
                        $attendanceRate = $attendanceProfiles[$index] ?? 0.90;
                        $isPresent = (mt_rand(1, 100) <= ($attendanceRate * 100));

                        AttendanceRecord::create([
                            'lecture_session_id' => $session->id,
                            'student_id'         => $student->id,
                            'status'             => $isPresent ? 'present' : 'absent',
                            'marked_by'          => $facultyUserId,
                            'marked_at'          => $date->copy()->setTimeFromTimeString($slot->end_time),
                        ]);

                        if ($isPresent) $totalAttendance++;
                    }
                }
            }

            $this->command->info("  ✓ {$assignment->subject->subject_code} ({$assignment->timetables->count()} slots/week)");
        }

        $this->command->info('');
        $this->command->info("=== ATTENDANCE RESTORE COMPLETE ===");
        $this->command->info("  Lecture Sessions Created : {$totalSessions}");
        $this->command->info("  Attendance Records Created: {$totalAttendance}");

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
