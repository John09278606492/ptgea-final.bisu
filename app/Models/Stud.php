<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
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

    public function siblings(): HasMany
    {
        return $this->hasMany(Sibling::class);
    }

    public function getFullNameAttribute()
    {
        return $this->lastname.', '.$this->firstname.', '.$this->middlename;
    }

    public static function countFullyPaidStudents(?int $schoolYearId): int
    {
        return self::when($schoolYearId, function ($query) use ($schoolYearId) {
            return $query->whereHas('enrollments', function ($subQuery) use ($schoolYearId) {
                $subQuery->where('schoolyear_id', $schoolYearId);
            });
        })
            ->whereHas('enrollments', function ($query) {
                $query->with(['collections', 'yearlevelpayments', 'pays']);
            })
            ->get()
            ->filter(function ($stud) {
                $enrollment = $stud->enrollments;
                $collectionsTotal = $enrollment->collections->sum('amount');
                $yearLevelPaymentsTotal = $enrollment->yearlevelpayments->sum('amount');
                $totalPays = $enrollment->pays->sum('amount');

                // Fully paid if total pays >= total required amount
                return $totalPays >= ($collectionsTotal + $yearLevelPaymentsTotal);
            })
            ->count();
    }

    public static function countUnpaidStudents(?int $schoolYearId): int
    {
        return self::when($schoolYearId, function ($query) use ($schoolYearId) {
            return $query->whereHas('enrollments', function ($subQuery) use ($schoolYearId) {
                $subQuery->where('schoolyear_id', $schoolYearId);
            });
        })
            ->whereHas('enrollments', function ($query) {
                $query->with(['collections', 'yearlevelpayments', 'pays']);
            })
            ->get()
            ->filter(function ($stud) {
                $enrollment = $stud->enrollments;
                $collectionsTotal = $enrollment->collections->sum('amount');
                $yearLevelPaymentsTotal = $enrollment->yearlevelpayments->sum('amount');
                $totalPays = $enrollment->pays->sum('amount');

                // Unpaid if total pays < total required amount
                return $totalPays < ($collectionsTotal + $yearLevelPaymentsTotal);
            })
            ->count();
    }
}
