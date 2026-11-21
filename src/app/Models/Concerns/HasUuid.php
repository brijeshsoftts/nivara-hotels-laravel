<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Support\Str;

trait HasUuid
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected static function bootHasUuid(): void
    {
        $dispatcher = EloquentModel::getEventDispatcher();

        if ($dispatcher === null) {
            return;
        }

        $dispatcher->listen('eloquent.creating: ' . static::class, static function (EloquentModel $model): void {
            if ($model->getAttribute($model->getKeyName()) === null) {
                $model->setAttribute($model->getKeyName(), (string) Str::uuid());
            }
        });
    }
}
