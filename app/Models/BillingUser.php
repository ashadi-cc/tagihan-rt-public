<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BillingUser extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'billing_id',
        'user_blok',
        'billing_name',
        'month',
        'year',
        'amount',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo('App\User');
    }

}
