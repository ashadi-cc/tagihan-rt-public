<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BillingFile extends Model
{
    protected $fillable = [
        'user_id',
        'driver', 
        'file_url',
    ];
}