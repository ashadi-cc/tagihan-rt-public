<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RtTransaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'payment_date',
        'organization_id',
        'billing_id',
        'name',
        'description',
        'status',
        'file_driver',
        'file_path',
        'debit',
        'credit',
        'created_by',
        'created_user_id',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
    ];

    /**
     * Get the organization that owns the transaction.
     */
    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the user who created the transaction.
     */
    public function createdUser()
    {
        return $this->belongsTo(User::class, 'created_user_id');
    }

    /**
     * Get all of the transaction's logs.
     */
    public function logs()
    {
        return $this->morphMany(Log::class, 'loggable');
    }

    /**
     * Get the balance (credit - debit).
     */
    public function getBalanceAttribute()
    {
        return $this->credit - $this->debit;
    }

    /**
     * Check if transaction is income (pemasukan).
     */
    public function isIncome()
    {
        return $this->credit > 0 && $this->debit == 0;
    }

    /**
     * Check if transaction is expense (pengeluaran).
     */
    public function isExpense()
    {
        return $this->debit > 0 && $this->credit == 0;
    }
}
