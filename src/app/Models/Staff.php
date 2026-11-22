<?php

namespace App\Models;

use App\Enums\Category;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Staff extends Model
{
    protected $table = 'staff';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'category' => Category::class,
        ];
    }

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

    public function roomAssignments(): HasMany
    {
        return $this->hasMany(HousekeeperRoomAssignment::class, 'user_id', 'user_id');
    }
}
