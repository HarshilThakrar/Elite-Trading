<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lead extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'lead_code',
        'name',
        'company_name',
        'email',
        'phone',
        'source',
        'status',
        'priority',
        'estimated_value',
        'assigned_to',
        'created_by',
        'expected_close_date',
        'last_contacted_at',
        'city',
        'state',
        'requirement_details',
        'customer_id',
    ];

    protected $casts = [
        'estimated_value' => 'decimal:2',
        'expected_close_date' => 'date',
        'last_contacted_at' => 'datetime',
    ];

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function remarks()
    {
        return $this->hasMany(LeadRemark::class)->latest();
    }

    public function latestRemark()
    {
        return $this->hasOne(LeadRemark::class)->latestOfMany();
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'New' => 'bg-info text-white',
            'Contacted' => 'bg-primary text-white',
            'Qualified' => 'bg-purple text-white',
            'Proposal Sent' => 'bg-warning text-dark',
            'Negotiation' => 'bg-secondary text-white',
            'Won' => 'bg-success text-white',
            'Lost' => 'bg-danger text-white',
            default => 'bg-light text-dark',
        };
    }

    public function getPriorityBadgeClassAttribute(): string
    {
        return match ($this->priority) {
            'Urgent' => 'bg-danger text-white',
            'High' => 'bg-warning text-dark',
            'Medium' => 'bg-info text-dark',
            'Low' => 'bg-secondary text-white',
            default => 'bg-light text-dark',
        };
    }
}
