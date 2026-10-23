<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pembayaran extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'organization_id',
        'payment_id',
        'old_payment_id',
        'payment_method',
        'payment_name',
        'payment_metadata',
        'payment_info_metadata',
        'payment_type',
        'payment_type_metadata',
        'payment_amount',
        'excess_amount',
        'payment_date',
        'user_id',
        'user_name',
        'user_blok',
        'created_by_user_id',
        'created_by_user_name',
        'status',
        'verified_by_user_id',
        'verified_by_user_name',
        'verified_at',
        'notes',
        'file_driver',
        'file_path',
        'is_sent_pushed',
    ];

    protected $casts = [
        'payment_metadata' => 'array',
        'payment_info_metadata' => 'array',
        'payment_amount' => 'decimal:2',
        'excess_amount' => 'decimal:2',
        'payment_date' => 'date',
        'verified_at' => 'datetime',
        'is_sent_pushed' => 'boolean',
    ];

    protected $dates = [
        'payment_date',
        'verified_at',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * Get the organization that owns the pembayaran.
     */
    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the display label for status.
     *
     * @return string
     */
    public function getStatusLabel()
    {
        $statusMap = [
            'verified' => 'Terverifikasi',
            'pending' => 'Menunggu Konfirmasi dari Admin',
            'rejected' => 'Ditolak',
        ];

        return $statusMap[$this->status] ?? ucfirst($this->status);
    }

    public function getFormattedPaymentTypeAttribute()
    {
        $typeMap = [
            'current_billing' => 'Iuran Rutin Bulanan',
            'previous_billing' => 'Tunggakan',
            'user_deposit' => 'Deposit',
            'manual_billing' => 'Iuran Khusus',
            'deposit_usage' => 'Penggunaan Deposit',
        ];

        return $typeMap[$this->payment_type] ?? ucfirst(str_replace('_', ' ', $this->payment_type));
    }

    /**
     * Get the formatted payment type metadata (converts MM-YYYY to human-readable Indonesian format).
     *
     * @return string|null
     */
    public function getFormattedPeriodAttribute()
    {
        if (!$this->payment_type_metadata) {
            return null;
        }

        if ($this->payment_type == 'previous_billing') {
            return '';
        }

        try {
            return \Carbon\Carbon::createFromFormat('m-Y', $this->payment_type_metadata)
                ->locale('id')
                ->isoFormat('MMMM YYYY');
        } catch (\Exception $e) {
            return $this->payment_type_metadata;
        }
    }

    /**
     * Get the payment method associated with the pembayaran.
     * Note: Payment may be soft deleted.
     */
    public function payment()
    {
        return $this->belongsTo(Payment::class)->withTrashed();
    }

    /**
     * Get the user (payer) associated with the pembayaran.
     * Note: User may be soft deleted.
     */
    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    /**
     * Get the user who created this pembayaran.
     * Note: User may be soft deleted.
     */
    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'created_by_user_id')->withTrashed();
    }

    /**
     * Get the user who verified this pembayaran.
     * Note: User may be soft deleted.
     */
    public function verifiedByUser()
    {
        return $this->belongsTo(User::class, 'verified_by_user_id')->withTrashed();
    }

    /**
     * Scope a query to only include pending pembayarans.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query to only include verified pembayarans.
     */
    public function scopeVerified($query)
    {
        return $query->where('status', 'verified');
    }

    /**
     * Scope a query to only include rejected pembayarans.
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Scope a query to filter by organization.
     */
    public function scopeForOrganization($query, $organizationId)
    {
        return $query->where('organization_id', $organizationId);
    }

    /**
     * Scope a query to filter by user.
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope a query to filter by payment date range.
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('payment_date', [$startDate, $endDate]);
    }

    /**
     * Scope a query to filter by payment type.
     */
    public function scopeByPaymentType($query, $paymentType)
    {
        return $query->where('payment_type', $paymentType);
    }

    /**
     * Check if pembayaran is pending.
     */
    public function isPending()
    {
        return $this->status === 'pending';
    }

    /**
     * Check if pembayaran is verified.
     */
    public function isVerified()
    {
        return $this->status === 'verified';
    }

    /**
     * Check if pembayaran is rejected.
     */
    public function isRejected()
    {
        return $this->status === 'rejected';
    }

    public function getLabelPaymentAmount($rawValue = false) {
        if ($this->status == 'verified' || ($this->excess_amount ?? 0) == 0) {
            if ($rawValue) {
                return $this->payment_amount;
            }
            return '<strong class="text-success">Rp ' . number_format($this->payment_amount, 0, ',', '.') . '</strong>';
        }

        $totalAmount = $this->payment_amount + ($this->excess_amount ?? 0);
        if ($rawValue) {
            return $totalAmount;
        }
        return '<strong class="text-danger">Rp ' . number_format($totalAmount, 0, ',', '.') . '</strong>';
    }

    /**
     * Get all of the pembayaran's logs.
     */
    public function logs()
    {
        return $this->morphMany(Log::class, 'loggable');
    }

    /**
     * Many-to-many relationship with billings through billing_pembayaran pivot table
     */
    public function tagihans()
    {
        return $this->belongsToMany(Tagihan::class, 'tagihan_pembayaran')
                    ->withPivot('amount')
                    ->withTimestamps();
    }
}
