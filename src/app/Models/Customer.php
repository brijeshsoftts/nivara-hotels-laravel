<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected $table = 'customers';

    protected $guarded = [];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function idProofImage(): BelongsTo
    {
        return $this->belongsTo(Image::class, 'id_proof_image_id');
    }

    public function signatureImage(): BelongsTo
    {
        return $this->belongsTo(Image::class, 'signature_image_id');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'customer_id', 'user_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'customer_id', 'user_id');
    }
}
