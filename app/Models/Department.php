<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'allow_past_attendance' => 'boolean',
        ];
    }

    public function programs(): HasMany
    {
        return $this->hasMany(Program::class);
    }

    public function faculty(): HasMany
    {
        return $this->hasMany(Faculty::class);
    }
}
