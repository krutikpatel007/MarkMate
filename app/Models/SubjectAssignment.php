<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubjectAssignment extends Model
{
    protected $guarded = [];

    public function faculty(): BelongsTo
    {
        return $this->belongsTo(Faculty::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function classSection(): BelongsTo
    {
        return $this->belongsTo(ClassSection::class);
    }

    public function timetables(): HasMany
    {
        return $this->hasMany(Timetable::class);
    }

    public function lectureSessions(): HasMany
    {
        return $this->hasMany(LectureSession::class);
    }

    public function internalMarkComponents(): HasMany
    {
        return $this->hasMany(InternalMarkComponent::class);
    }

    public function internalMarks(): HasMany
    {
        return $this->hasMany(InternalMark::class);
    }

    public function reEvaluationRequests(): HasMany
    {
        return $this->hasMany(ReEvaluationRequest::class);
    }
}
