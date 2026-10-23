<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Log extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'username',
        'description',
        'url_link',
        'loggable_id',
        'loggable_type',
    ];

    /**
     * Get the parent loggable model (User, Billing, Payment, etc.).
     */
    public function loggable()
    {
        return $this->morphTo();
    }

    /**
     * Get the organization that owns the log.
     */
    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
}
