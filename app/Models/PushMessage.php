<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PushMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'message_type',
        'recipient_id',
        'message_content',
    ];

    /**
     * Get the organization that owns the push message.
     */
    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get all of the push message's logs.
     */
    public function logs()
    {
        return $this->morphMany(Log::class, 'loggable');
    }
}