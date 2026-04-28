<?php
namespace App\Traits;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

trait Auditable
{
    public static function bootAuditable()
    {
        static::created(function ($model) {
            self::log('created', $model, null, $model->getAttributes());
        });

        static::updated(function ($model) {
            $changes = $model->getChanges();

            if (empty($changes))
                return;

            self::log(
                'updated',
                $model,
                array_intersect_key($model->getOriginal(), $changes),
                $changes
            );
        });

        static::deleted(function ($model) {
            self::log('deleted', $model, $model->getOriginal(), null);
        });
    }

    protected static function log($event, $model, $old, $new)
    {
        unset(
            $old['password'],
            $old['remember_token'],
            $new['password'],
            $new['remember_token']
        );

        AuditLog::create([
            'user_id' => Auth::id(),
            'event' => $event,
            'model_type' => get_class($model),
            'model_id' => $model->id,
            'old_values' => $old,
            'new_values' => $new,
        ]);
    }
}
