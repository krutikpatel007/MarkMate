<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InternalMark extends Model
{
    protected $guarded = [];

    protected $table = 'internal_marks';

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
        ];
    }

    public function subjectAssignment(): BelongsTo
    {
        return $this->belongsTo(SubjectAssignment::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function markedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'marked_by');
    }

    public function componentValues(): HasMany
    {
        return $this->hasMany(InternalMarkValue::class, 'internal_mark_id');
    }

    public function isSubmitted(): bool
    {
        return $this->status === 'submitted' || $this->submitted_at !== null;
    }
}
