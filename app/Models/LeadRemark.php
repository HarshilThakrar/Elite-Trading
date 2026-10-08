<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeadRemark extends Model
{
    use HasFactory;

    protected $fillable = [
        'lead_id',
        'user_id',
        'remark',
        'action_type',
        'old_status',
        'new_status',
        'is_notification',
    ];

    protected $casts = [
        'is_notification' => 'boolean',
    ];

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getActionBadgeClassAttribute(): string
    {
        return match ($this->action_type) {
            'created' => 'bg-success text-white',
            'updated' => 'bg-primary text-white',
            'status_changed' => 'bg-warning text-dark',
            'remark' => 'bg-info text-white',
            default => 'bg-secondary text-white',
        };
    }
}
