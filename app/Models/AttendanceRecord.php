<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceRecord extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'marked_at' => 'datetime',
        ];
    }

    public function lectureSession(): BelongsTo
    {
        return $this->belongsTo(LectureSession::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function marker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'marked_by');
    }
}
