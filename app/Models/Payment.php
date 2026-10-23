<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use SoftDeletes;

     /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'organization_id',
        'name',
        'payment_type',
        'metadata',
        'description',
        'disabled',
    ];

    protected $casts = [
        'metadata' => 'array',
        'disabled' => 'boolean',
    ];

    /**
     * Get the organization that owns the payment.
     */
    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get all of the payment's logs.
     */
    public function logs()
    {
        return $this->morphMany(Log::class, 'loggable');
    }

    public function pembayarans()
    {
        return $this->hasMany(Pembayaran::class, 'payment_id');
    }

    // get total verified payment amount for a given month and year
    public function totalVerifiedAmount($month, $year)
    {
        return $this->pembayarans()
            ->where('status', 'verified')
            ->where('payment_type', '!=', 'deposit_usage')
            ->whereYear('payment_date', $year)
            ->whereMonth('payment_date', $month)
            ->sum('payment_amount');
    }
}
