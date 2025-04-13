<?php

namespace App\Models;

use App\Enum\EncounterDateType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Notifications\Notifiable;

class Batch extends Model
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'provider_id',
        'insurer_id',
        'processing_date',
        'preferred_date_type',
        'total_value',
        'batch_identifier',
        'claim_count',
    ];

    protected $casts = [
        'preferred_date_type' => EncounterDateType::class,
        'processing_date' => 'date'
    ];

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class, 'provider_id');
    }

    public function insurer(): BelongsTo
    {
        return $this->belongsTo(Insurer::class, 'insurer_id');
    }
}
