<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tagihan extends Model
{
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'organization_id',
        'billing_id',
        'billing_name',
        'billing_type',
        'billing_amount',
        'billing_priority',
        'billing_generated_date',
        'description',
        'payment_amount',
        'user_id',
        'user_name',
        'user_blok_number',
        'created_by_user_id',
        'created_by_user_name'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'billing_amount' => 'decimal:2',
        'payment_amount' => 'decimal:2',
        'billing_priority' => 'integer',
        'billing_generated_date' => 'date',
    ];

    /**
     * Get the organization that owns the tagihan.
     */
    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the billing associated with the tagihan.
     */
    public function billing()
    {
        return $this->belongsTo(Billing::class)->withTrashed();
    }

    /**
     * Get the user who created the tagihan.
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * Get all of the tagihan's logs.
     */
    public function logs()
    {
        return $this->morphMany(Log::class, 'loggable');
    }

    public function getPeriodAttribute()
    {
        // return month and year format from billing_generated_date
        return $this->billing_generated_date->format('m/Y');
    }
}
