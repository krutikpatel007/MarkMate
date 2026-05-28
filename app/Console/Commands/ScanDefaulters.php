<?php

namespace App\Console\Commands;

use App\Models\AttendanceRecord;
use App\Models\InAppNotification;
use App\Models\Student;
use App\Models\Program;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ScanDefaulters extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:scan-defaulters';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scan student attendance percentages and warn those below 75%';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting student attendance scan...');

        $students = Student::with('user')->where('status', 'active')->get();

        foreach ($students as $student) {
            $records = AttendanceRecord::query()
                ->join('lecture_sessions', 'lecture_sessions.id', '=', 'attendance_records.lecture_session_id')
                ->where('attendance_records.student_id', $student->id)
                ->whereIn('lecture_sessions.status', ['conducted', 'locked'])
                ->select([
                    DB::raw("sum(case when attendance_records.status = 'present' then 1 else 0 end) as present_count"),
                    DB::raw("count(*) as conducted_count")
                ])
                ->first();

            $conducted = $records->conducted_count ?? 0;
            $present = $records->present_count ?? 0;

            if ($conducted > 0) {
                $percentage = round(($present / $conducted) * 100, 2);
                
                if ($percentage < 75.00) {
                    $title = 'Low Attendance Warning';
                    $message = "Your overall attendance is currently {$percentage}%, which is below the mandatory 75% university requirement. Please attend upcoming lectures to avoid being barred from exams.";

                    // Avoid duplicate warnings on the same day
                    $exists = InAppNotification::where('user_id', $student->user_id)
                        ->where('title', $title)
                        ->whereDate('created_at', today())
                        ->exists();

                    if (!$exists) {
                        InAppNotification::create([
                            'user_id' => $student->user_id,
                            'title' => $title,
                            'message' => $message,
                            'type' => 'warning',
                        ]);
                        $this->info("Sent warning to {$student->user->name} ({$percentage}%)");
                    }
                }
            }
        }

        $this->info('Attendance scan completed successfully!');
    }
}
