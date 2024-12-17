<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Studentschoolyearsemesterpayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'studentschoolyearsemester_id',
        'collection_id',
    ];

    public function studentschoolyearsemester(): BelongsTo
    {
        return $this->belongsTo(Studentschoolyearsemester::class, 'studentschoolyearsemester_id');
    }

    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class, 'college_id');
    }
}
