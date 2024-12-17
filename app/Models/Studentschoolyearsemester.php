<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Studentschoolyearsemester extends Model
{
    use HasFactory;

    protected $fillable = [
        'studentschoolyear_id',
        'semester_id',
    ];

    public function studentschoolyear(): BelongsTo
    {
        return $this->belongsTo(Studentschoolyear::class, 'studentschoolyear_id');
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class, 'semester_id');
    }

    public function studentschoolyearsemesterpayments(): HasMany
    {
        return $this->hasMany(Studentschoolyearsemesterpayment::class, 'studentschoolyearsemester_id');
    }
}
