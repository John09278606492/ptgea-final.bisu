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
}