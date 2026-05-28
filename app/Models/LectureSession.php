<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LectureSession extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'lecture_date' => 'date',
            'submitted_at' => 'datetime',
            'locked_at' => 'datetime',
        ];
    }

    public function timetable(): BelongsTo
    {
        return $this->belongsTo(Timetable::class);
    }

    public function subjectAssignment(): BelongsTo
    {
        return $this->belongsTo(SubjectAssignment::class);
    }

    public function extraLectureRequest(): BelongsTo
    {
        return $this->belongsTo(ExtraLectureRequest::class);
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function correctionRequests(): HasMany
    {
        return $this->hasMany(AttendanceCorrectionRequest::class);
    }

    public function isLocked(): bool
    {
        return $this->status === 'locked' || $this->locked_at !== null;
    }

    public function canEditAttendance(): bool
    {
        if ($this->isLocked() || $this->status === 'cancelled') {
            return false;
        }

        return $this->submitted_at === null;
    }
}
