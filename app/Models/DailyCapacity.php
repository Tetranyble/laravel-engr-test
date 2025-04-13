<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyCapacity extends Model
{
    use HasFactory;

    protected $fillable = [
        'insurer_id',
        'processing_date',
        'used_capacity',
    ];

    protected $casts = [
        'processing_date' => 'date',
    ];

    public function insurer(): BelongsTo
    {
        return $this->belongsTo(Insurer::class, 'insurer_id');

    }
}
