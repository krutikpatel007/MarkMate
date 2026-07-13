<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Faculty extends Model
{
    protected $table = 'faculty';

    protected $guarded = [];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function subjectAssignments(): HasMany
    {
        return $this->hasMany(SubjectAssignment::class);
    }

    public function extraLectureRequests(): HasMany
    {
        return $this->hasMany(ExtraLectureRequest::class);
    }

    public function attendanceCorrectionRequests(): HasMany
    {
        return $this->hasMany(AttendanceCorrectionRequest::class);
    }

    public function facultyLeaveRequests(): HasMany
    {
        return $this->hasMany(FacultyLeaveRequest::class);
    }

    public function salaryConfig(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(FacultySalaryConfig::class);
    }

    public function payslips(): HasMany
    {
        return $this->hasMany(FacultyPayslip::class)->orderByDesc('year')->orderByDesc('month');
    }

    public function appraisals(): HasMany
    {
        return $this->hasMany(FacultyAppraisal::class)->orderByDesc('created_at');
    }

    public function feedbacks(): \Illuminate\Database\Eloquent\Relations\HasManyThrough
    {
        return $this->hasManyThrough(FacultyFeedback::class, SubjectAssignment::class);
    }

    public function weeklyLoadHours(): float
    {
        $timetableSlots = \App\Models\Timetable::whereIn('subject_assignment_id', $this->subjectAssignments->pluck('id'))
            ->where('status', 'active')
            ->get();

        // Group intervals by day of week
        $slotsByDay = [];
        foreach ($timetableSlots as $slot) {
            $slotsByDay[$slot->day_of_week][] = [
                'start' => strtotime($slot->start_time),
                'end' => strtotime($slot->end_time)
            ];
        }

        $totalHours = 0;

        foreach ($slotsByDay as $day => $intervals) {
            // Sort intervals by start time
            usort($intervals, function ($a, $b) {
                return $a['start'] <=> $b['start'];
            });

            // Merge overlapping intervals
            $merged = [];
            foreach ($intervals as $interval) {
                if (empty($merged)) {
                    $merged[] = $interval;
                } else {
                    $last = &$merged[count($merged) - 1];
                    if ($interval['start'] < $last['end']) {
                        // Overlap! Extend the end time
                        $last['end'] = max($last['end'], $interval['end']);
                    } else {
                        // No overlap
                        $merged[] = $interval;
                    }
                }
            }

            // Sum durations of merged intervals
            foreach ($merged as $interval) {
                if ($interval['end'] > $interval['start']) {
                    $totalHours += ($interval['end'] - $interval['start']) / 3600;
                }
            }
        }

        return round($totalHours, 2);
    }
}
