<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Program extends Model
{
    use HasFactory;

    protected $fillable = [
        'college_id',
        'program',
    ];

    public function college(): BelongsTo
    {
        return $this->belongsTo(College::class, 'college_id');
    }

    public function yearlevels(): HasMany
    {
        return $this->hasMany(Yearlevel::class, 'program_id');
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'program_id');
    }
}
