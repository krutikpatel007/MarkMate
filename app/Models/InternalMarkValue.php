<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InternalMarkValue extends Model
{
    protected $guarded = [];

    protected $table = 'internal_marks_values';

    public function internalMark(): BelongsTo
    {
        return $this->belongsTo(InternalMark::class, 'internal_mark_id');
    }

    public function component(): BelongsTo
    {
        return $this->belongsTo(InternalMarkComponent::class, 'internal_marks_component_id');
    }
}
