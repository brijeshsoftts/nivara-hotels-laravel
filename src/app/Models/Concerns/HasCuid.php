<?php

namespace App\Models\Concerns;

use App\Support\Cuid;
use Illuminate\Database\Eloquent\Model as EloquentModel;

trait HasCuid
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected static function bootHasCuid(): void
    {
        $dispatcher = EloquentModel::getEventDispatcher();

        if ($dispatcher === null) {
            return;
        }

        $dispatcher->listen('eloquent.creating: ' . static::class, static function (EloquentModel $model): void {
            if ($model->getAttribute($model->getKeyName()) === null) {
                $model->setAttribute($model->getKeyName(), Cuid::generate());
            }
        });
    }
}
