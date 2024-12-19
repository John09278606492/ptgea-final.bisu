<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'studentidn',
        'firstname',
        'middlename',
        'lastname',
        'status',
    ];

    public function scolleges(): HasOne
    {
        return $this->hasOne(Scollege::class);
    }

    public function syears(): HasOne
    {
        return $this->hasOne(Syear::class);
    }

    // public function getTotalCollectionAttribute()
    // {
    //     // Flatten all the related `yearlevelpayments` across `scolleges` and sum their amounts
    //     return $this->scolleges->flatMap->yearlevelpayments->sum('amount');
    // }
}
