<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClaimItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'claim_id',
        'name',
        'unit_price',
        'quantity',
    ];

    public function claim(): BelongsTo
    {
        return $this->belongsTo(Claim::class, 'claim_id');
    }
}
