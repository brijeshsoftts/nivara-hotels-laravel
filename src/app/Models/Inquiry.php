<?php

namespace App\Models;

class Inquiry extends Model
{
    protected $table = 'inquiries';

    protected $guarded = [];

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }
}
