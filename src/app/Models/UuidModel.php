<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model as EloquentModel;

abstract class UuidModel extends EloquentModel
{
    use HasUuid;
}
