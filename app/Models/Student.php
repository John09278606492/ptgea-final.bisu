<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'studentidn',
        'firstname',
        'middlename',
        'lastname',
    ];

    public function studentschoolyears(): HasMany
    {
        return $this->hasMany(Studentschoolyear::class, 'student_id');
    }
}
