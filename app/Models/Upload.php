<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Upload extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'file_name', 'file_type'
    ];

    /**
     * Get the own of the upload
     */
    public function user() 
    {
        return $this->belongsTo('App\User');
    }

}
