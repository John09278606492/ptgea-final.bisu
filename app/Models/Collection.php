<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Collection extends Model
{
    use HasFactory;

    protected $fillable = [
        'semester_id',
        'amount',
        'description',
    ];

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class.'semester_id');
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'collection_id');
    }
}
