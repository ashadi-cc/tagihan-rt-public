<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Letter extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'title',
        'nomor_surat',
        'tanggal_surat',
        'description',
        'status',
        'show_on_public',
        'files',
        'external_links',
    ];

    protected $casts = [
        'tanggal_surat' => 'date',
        'show_on_public' => 'boolean',
        'files' => 'array',
        'external_links' => 'array',
    ];

    /**
     * Get the organization that owns the letter.
     */
    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get all of the letter's logs.
     */
    public function logs()
    {
        return $this->morphMany(Log::class, 'loggable');
    }

    /**
     * Scope to get only published letters.
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    /**
     * Scope to get only letters visible on public page.
     */
    public function scopePublicVisible($query)
    {
        return $query->where('show_on_public', true)->where('status', 'published');
    }
}
