<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ManualBillingSchedule extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'billing_id',
        'amount',
        'user_ids',
        'scheduled_at',
        'is_generated',
        'created_by_user_id'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'user_ids' => 'array',
        'scheduled_at' => 'date',
        'is_generated' => 'boolean',
    ];

    /**
     * Get the billing associated with the schedule.
     */
    public function billing()
    {
        return $this->belongsTo(Billing::class);
    }

    /**
     * Get the user who created the schedule.
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * Get the organization through billing.
     */
    public function organization()
    {
        return $this->hasOneThrough(Organization::class, Billing::class, 'id', 'id', 'billing_id', 'organization_id');
    }
}
