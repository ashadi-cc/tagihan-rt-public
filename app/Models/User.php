<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles, SoftDeletes, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password', 'username', 'blok', 'default_password', 'blok_name', 'blok_number', 'phone_number', 'whatsapp_id', 'jabatan', 'notes'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    //overrired username value to lowercase 
    public function setUsernameAttribute($value)
    {
        $this->attributes['username'] = strtolower($value);
    }

    public function getEmailAttribute($value)
    {
        if (strpos($value, config('default.hostmail'))) {
            return '';
        }

        return $value;
    }

    public function setBlokAttribute($value)
    {
        $blok =  strtoupper($value);
        $this->attributes['blok'] = $blok;

        $splitBlok = explode('-', $blok); 
        if (count($splitBlok) == 2) {
            $this->attributes['blok_name'] = $splitBlok[0];
            $this->attributes['blok_number'] = $splitBlok[1];
        }
    }

    public function scopeWarga($query)
    {
        return $query->where('username', '<>', 'admin');
    }

    /**
     * Get the uploads for the user
     */
    public function uploads()
    {
        return $this->hasMany('App\Models\Upload');
    }

    public function billings()
    {
        return $this->hasMany('App\Models\BillingUser');
    }

    /**
     * Many-to-many relationship with billings through user_billings pivot table
     */
    public function userBillings()
    {
        return $this->belongsToMany(Billing::class, 'user_billings')
                    ->withTimestamps();
    }

    /**
     * Get all of the user's logs.
     */
    public function logs()
    {
        return $this->morphMany('App\Models\Log', 'loggable');
    }

    public function pembayarans()
    {
        return $this->hasMany(Pembayaran::class);
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get current month tagihan total (unpaid amount)
     */
    public function getCurrentMonthTagihan($excludeManual = true)
    {
        $billingTypes = ['rutin_perbulan'];
        if (!$excludeManual) {
            $billingTypes[] = 'manual';
        }

        return Tagihan::where('organization_id', $this->organization_id)
            ->where('user_id', $this->id)
            ->whereIn('billing_type', $billingTypes)
            ->whereYear('billing_generated_date', now()->year)
            ->whereMonth('billing_generated_date', now()->month)
            ->sum(\DB::raw('billing_amount - payment_amount'));
    }

    /**
     * Query for unpaid billing rows from previous months (tunggakan).
     * Shared by getTunggakanTotal() and the resident-facing tunggakan detail page
     * so the total and the line-item breakdown never drift apart.
     */
    public function tunggakanQuery($excludeManual = true)
    {
        $currentMonth = now()->format('Y-m');
        $billingTypes = ['rutin_perbulan'];
        if (!$excludeManual) {
            $billingTypes[] = 'manual';
        }

        return Tagihan::where('organization_id', $this->organization_id)
            ->where('user_id', $this->id)
            ->where(function($query) use ($currentMonth, $billingTypes) {
                $query->whereRaw("DATE_FORMAT(billing_generated_date, '%Y-%m') < ?", [$currentMonth])
                       ->whereIn('billing_type', $billingTypes)
                      ->orWhere('billing_type', 'tunggakan_warga');
            })
            ->whereRaw('billing_amount > payment_amount');
    }

    /**
     * Get tunggakan total (unpaid amount from previous months)
     */
    public function getTunggakanTotal($excludeManual = true)
    {
        return $this->tunggakanQuery($excludeManual)->sum(\DB::raw('billing_amount - payment_amount'));
    }

    public function getManualBillingTotal()
    {
        return Tagihan::where('organization_id', $this->organization_id)
            ->where('user_id', $this->id)
            ->where('billing_type', 'manual')
            ->whereRaw('billing_amount > payment_amount')
            ->sum(\DB::raw('billing_amount - payment_amount'));
    }
}
