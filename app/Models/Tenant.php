<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tenant extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'name',
        'contact_info',
        'address',
        'unit_number',
        'description',
        'file_driver',
        'file_path',
    ];

    /**
     * Get the organization that owns the tenant.
     */
    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get all of the tenant's logs.
     */
    public function logs()
    {
        return $this->morphMany(Log::class, 'loggable');
    }
}
