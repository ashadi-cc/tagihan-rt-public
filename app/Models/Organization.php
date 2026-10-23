<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Organization extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'whatsapp_admin_group_id',
        'whatsapp_member_group_id',
        'whatsapp_notif_startup_number',
        'whatsapp_enable_registration',
    ];

    protected $casts = [
        'whatsapp_enable_registration' => 'boolean',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function getWhatsappAdminGroupId(): ?string
    {
        return $this->whatsapp_admin_group_id ?: config('bot.whatsapp_admin_group_id');
    }

    public function getWhatsappMemberGroupId(): ?string
    {
        return $this->whatsapp_member_group_id ?: config('bot.whatsapp_member_group_id');
    }

    public function getWhatsappNotifStartupNumber(): ?string
    {
        return $this->whatsapp_notif_startup_number ?: config('bot.whatsapp_notif_startup_number');
    }

    public function getWhatsappEnableRegistration(): bool
    {
        return $this->whatsapp_enable_registration ?? config('bot.whatsapp_enable_registration');
    }
}
