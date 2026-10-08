<?php

namespace App\Traits;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

trait Auditable
{
    public static function bootAuditable()
    {
        static::created(function ($model) {
            self::logAudit('created', $model);
        });

        static::updated(function ($model) {
            self::logAudit('updated', $model);
        });

        static::deleted(function ($model) {
            self::logAudit('deleted', $model);
        });
    }

    protected static function logAudit($action, $model)
    {
        // Don't log if running in console (e.g. migrations/seeders)
        if (app()->runningInConsole()) {
            return;
        }

        $oldValues = $action !== 'created' ? $model->getOriginal() : null;
        $newValues = $action !== 'deleted' ? $model->getAttributes() : null;

        // If updated, only store changes
        if ($action === 'updated') {
            $changes = $model->getDirty();
            if (empty($changes)) return;
            
            $oldValues = array_intersect_key($oldValues, $changes);
            $newValues = $changes;
        }

        AuditLog::create([
            'user_id' => Auth::id() ?? 1, // fallback to 1 if no user (e.g. jobs)
            'action' => $action,
            'model_type' => get_class($model),
            'model_id' => $model->id,
            'old_values' => $oldValues,
            'new_values' => $newValues,
        ]);
    }
}
