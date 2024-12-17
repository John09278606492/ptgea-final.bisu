<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Studentschoolyear extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'schoolyear_id',
        'status',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function schoolYear(): BelongsTo
    {
        return $this->belongsTo(Schoolyear::class, 'schoolyear_id');
    }

    public function studentschoolyearsemesters(): HasMany
    {
        return $this->hasMany(Studentschoolyearsemester::class, 'studentschoolyear_id');
    }
}
