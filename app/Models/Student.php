<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Student extends Model
{
    protected $primaryKey = null;

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class, "school_number", "school_number");
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class);
    }

    public function grade(): HasOne
    {
        return $this->hasOne(Grade::class, "grade_level", "grade_level");
    }
}
