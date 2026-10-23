<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Inventory extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'item_name',
        'quantity',
        'condition',
        'stored_location',
        'description',
        'photos',
    ];

    protected $casts = [
        'photos' => 'array',
    ];

    /**
     * Get the organization that owns the inventory.
     */
    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get all of the inventory's logs.
     */
    public function logs()
    {
        return $this->morphMany(Log::class, 'loggable');
    }
}
