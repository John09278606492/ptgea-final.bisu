<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Stud extends Model
{
    use HasFactory;

    protected $fillable = [
        'studentidn',
        'firstname',
        'middlename',
        'lastname',
        'status',
    ];

    public function enrollments(): HasOne
    {
        return $this->hasOne(Enrollment::class);
    }

    public function getFullNameAttribute()
    {
        return $this->lastname.', '.$this->firstname.', '.$this->middlename;
    }

    public static function countBySchoolYear(?int $schoolYearId): int
    {
        if (is_null($schoolYearId)) {
            // Return the total count of students if no schoolyear_id is provided
            return self::count();
        }

        return self::whereHas('enrollments', function ($query) use ($schoolYearId) {
            $query->where('schoolyear_id', $schoolYearId);
        })->count();
    }
}
