<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Enrollment extends Model
{
    use HasFactory;

    protected $fillable = [
        'stud_id',
        'college_id',
        'program_id',
        'yearlevel_id',
        'schoolyear_id',
        'status',
    ];

    public function stud(): BelongsTo
    {
        return $this->belongsTo(Stud::class);
    }

    public function college(): BelongsTo
    {
        return $this->belongsTo(College::class);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function yearlevel(): BelongsTo
    {
        return $this->belongsTo(Yearlevel::class);
    }

    public function schoolyear(): BelongsTo
    {
        return $this->belongsTo(Schoolyear::class);
    }

    public function semesters(): BelongsToMany
    {
        return $this->belongsToMany(Semester::class)
            ->withTimestamps();
    }

    public function collections(): BelongsToMany
    {
        return $this->belongsToMany(Collection::class, 'collection_enrollment')
            ->withTimestamps();
    }

    public function yearlevelpayments(): BelongsToMany
    {
        return $this->belongsToMany(Yearlevelpayments::class)
            ->withTimestamps();
    }

    public function pays(): HasMany
    {
        return $this->hasMany(Pay::class);
    }

    public static function summarizeAmounts()
    {
        $total = self::with(['collections', 'yearlevelpayments'])
            ->get()
            ->sum(function ($enrollment) {
                $collectionsTotal = $enrollment->collections->sum('amount');
                $yearLevelPaymentsTotal = $enrollment->yearlevelpayments->sum('amount');

                return $collectionsTotal + $yearLevelPaymentsTotal;
            });

        return '₱'.number_format($total, 2);
    }
}
