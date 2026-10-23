<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Event extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'title',
        'description',
        'event_date',
        'event_time',
        'location',
        'persistent_event',
        'files',
    ];

    protected $casts = [
        'event_date' => 'date',
        'persistent_event' => 'boolean',
        'files' => 'array',
    ];

    /**
     * Get the organization that owns the event.
     */
    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get all of the event's logs.
     */
    public function logs()
    {
        return $this->morphMany(Log::class, 'loggable');
    }
}
