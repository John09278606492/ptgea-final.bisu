<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'schoolyear_id',
        'semester_id',
        'collection_id',
        'college_id',
        'program_id',
        'yearlevel_id',
        'yearlevelpayment_id',
        'studentidn',
        'firstname',
        'middlename',
        'lastname',
        'status',
    ];

    public function schoolYear(): BelongsTo
    {
        return $this->belongsTo(Schoolyear::class, 'schoolyear_id');
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class, 'semester_id');
    }

    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class, 'collection_id');
    }

    public function college(): BelongsTo
    {
        return $this->belongsTo(College::class, 'college_id');
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class, 'program_id');
    }

    public function yearlevel(): BelongsTo
    {
        return $this->belongsTo(Yearlevel::class, 'yearlevel_id');
    }

    public function yearlevelpayment(): BelongsTo
    {
        return $this->belongsTo(Yearlevelpayments::class, 'yearlevelpayment_id');
    }
}
