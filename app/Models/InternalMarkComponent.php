<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InternalMarkComponent extends Model
{
    protected $guarded = [];

    protected $table = 'internal_marks_components';

    public function subjectAssignment(): BelongsTo
    {
        return $this->belongsTo(SubjectAssignment::class);
    }

    public function values(): HasMany
    {
        return $this->hasMany(InternalMarkValue::class, 'internal_marks_component_id');
    }
}
