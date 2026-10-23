<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Billing extends Model
{
    use SoftDeletes; 
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'description', 'type', 'billing_type', 'disabled', 'amount', 'organization_id', 'priority'
    ];

    /**
     * Get all of the billing's logs.
     */
    public function logs()
    {
        return $this->morphMany(Log::class, 'loggable');
    }

    /**
     * Get the organization that owns the billing.
     */
    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function manualBillingSchedules()
    {
        return $this->hasMany(ManualBillingSchedule::class);
    }

    /**
     * Many-to-many relationship with users through user_billings pivot table
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_billings')
                    ->withTimestamps();
    }

    /**
     * Many-to-many relationship with pembayarans through billing_pembayaran pivot table
     */
    public function pembayarans()
    {
        return $this->belongsToMany(Pembayaran::class, 'billing_pembayaran')
                    ->withPivot('amount', 'notes')
                    ->withTimestamps();
    }
}
