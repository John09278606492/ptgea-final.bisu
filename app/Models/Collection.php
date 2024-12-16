<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Semester;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        return $this->belongsTo(Semester::class);
    }

}
