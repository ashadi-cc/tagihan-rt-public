<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use App\Models\Billing;
use App\Models\Tagihan;
use Illuminate\Support\Facades\Log;
use App\Jobs\PembayaranOtomatisDepositJob;

class BuatTagihanRutinJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $billingGeneratedDate;
    protected $billingUpdateId;
    protected $removedUsers = [];

    /**
     * Create a new job instance.
     */
    public function __construct($billingGeneratedDate = null, $billingUpdateId = null, $removedUsers = [])
    {
        $this->billingGeneratedDate = $billingGeneratedDate ?? now()->format('Y-m-d');
        $this->billingUpdateId = $billingUpdateId;
        $this->removedUsers = $removedUsers;
    }

    /**
     * Get the middleware the job should pass through.
     */
    public function middleware()
    {
        return [
            (new WithoutOverlapping('buat-tagihan-rutin'))
                ->releaseAfter(60) // Release lock after 60 seconds if job fails
                ->expireAfter(180) // Expire lock after 3 minutes
        ];
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $billings = Billing::where('billing_type', 'rutin_perbulan')
                            ->with('users')
                            ->where('disabled', false)
                            ->get();

        foreach ($billings as $billing) {
            if ($this->billingUpdateId && $billing->id != $this->billingUpdateId) {
                continue; // skip if this billing is not the one being updated
            }
            
            foreach ($billing->users as $user) {
                // Check if the tagihan already exists for this user, month and year
                $existingTagihan = Tagihan::where('organization_id', $billing->organization_id)
                                        ->where('user_id', $user->id)
                                        ->where('billing_id', $billing->id)
                                        ->whereYear('billing_generated_date', date('Y', strtotime($this->billingGeneratedDate)))
                                        ->whereMonth('billing_generated_date', date('m', strtotime($this->billingGeneratedDate)))
                                        ->first();
                
                if ($existingTagihan) {
                    Log::info("Tagihan already exists for user_id: {$user->id}, billing_generated_date: {$this->billingGeneratedDate}");
                    continue; // skip to next user
                }

                Tagihan::create([
                    'organization_id' => $billing->organization_id,
                    'billing_id' => $billing->id,
                    'billing_name' => $billing->name,
                    'billing_type' => $billing->billing_type,
                    'billing_amount' => $billing->amount,
                    'billing_priority' => $billing->priority,
                    'billing_generated_date' => $this->billingGeneratedDate,
                    'payment_amount' => 0,
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'user_blok_number' => $user->blok,
                    'created_by_user_id' => null,
                    'created_by_user_name' => 'System',
                ]);

                // Create log for the user
                $user->logs()->create([
                    'organization_id' => $billing->organization_id,
                    'username' => 'System',
                    'description' => "Tagihan rutin '{$billing->name}' sebesar Rp " . number_format($billing->amount, 0, ',', '.') . " telah dibuat pada tanggal " . date('d M Y', strtotime($this->billingGeneratedDate)),
                    'url_link' => '/admin/master/tagihan',
                ]);
            }
        }

        if ($this->billingUpdateId && !empty($this->removedUsers)) {
            // Remove tagihans for removed users
            foreach ($this->removedUsers as $removedUserId) {
                $deletedCount = Tagihan::where('billing_id', $this->billingUpdateId)
                                    ->where('user_id', $removedUserId)
                                    ->whereYear('billing_generated_date', date('Y', strtotime($this->billingGeneratedDate)))
                                    ->whereMonth('billing_generated_date', date('m', strtotime($this->billingGeneratedDate)))
                                    ->where('payment_amount', 0) // only delete if not paid
                                    ->delete();

                Log::info("Deleted {$deletedCount} tagihan(s) for removed user_id: {$removedUserId}");
            }
        }

        // Dispatch pembayaran otomatis deposit job after all billings are created
        Log::info('Dispatching PembayaranOtomatisDepositJob after rutin billing creation');
        PembayaranOtomatisDepositJob::dispatch();
    }
}
