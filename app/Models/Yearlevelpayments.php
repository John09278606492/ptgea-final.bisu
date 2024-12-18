<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Yearlevelpayments extends Model
{
    use HasFactory;

    protected $fillable = [
        'yearlevel_id',
        'amount',
        'description',
    ];

    /**
     * Get the user that owns the Yearlevelpayments
     */
    public function yearlevel(): BelongsTo
    {
        return $this->belongsTo(Yearlevel::class, 'yearlevel_id');
    }
}
