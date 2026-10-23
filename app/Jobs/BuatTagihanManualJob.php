<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use App\Models\ManualBillingSchedule;
use App\Models\Tagihan;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use App\Jobs\PembayaranOtomatisDepositJob;

class BuatTagihanManualJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $schedule;
    protected $cron;

    /**
     * Create a new job instance.
     */
    public function __construct(ManualBillingSchedule $schedule, $cron = false)
    {
        $this->schedule = $schedule;
        $this->cron = $cron;
    }

    /**
     * Get the middleware the job should pass through.
     */
    public function middleware()
    {
        return [
            (new WithoutOverlapping('buat-tagihan-manual'))
                ->releaseAfter(60) // Release lock after 60 seconds if job fails
                ->expireAfter(180) // Expire lock after 3 minutes
        ];
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // if the schedule is already generated, skip
        if ($this->schedule->is_generated) {
            return;
        }

        // check if the billing is disabled
        if ($this->schedule->billing->disabled) {
            return;
        }

        // check if the scheduled date is in the future 
        if ($this->schedule->scheduled_at->isFuture()) {
            return;
        }

        $oganizationId = $this->schedule->billing->organization_id;
        //load billing 
        $billing = $this->schedule->billing;

        $users = User::whereIn('id', $this->schedule->user_ids)
                    ->where('organization_id', $oganizationId)
                    ->get();

        foreach ($users as $user) {
            // first we check if the tagihan already exists for this user and billing
            $existingTagihan = Tagihan::where('organization_id', $oganizationId)
                                    ->where('billing_id', $billing->id)
                                    ->where('user_id', $user->id)
                                    ->where('billing_generated_date', $this->schedule->scheduled_at)
                                    ->first();
            
            if ($existingTagihan) {
                Log::info("Tagihan already exists for user_id: {$user->id}, billing_id: {$billing->id} on date: {$this->schedule->scheduled_at}");
                continue; // skip to next user
            }

            $createdBy = $this->schedule->createdBy ? $this->schedule->createdBy->name : 'System';
            if ($this->cron) {
                $createdBy = 'System';
            }

            Tagihan::create([
                'organization_id' => $oganizationId,
                'billing_id' => $billing->id,
                'billing_name' => $billing->name,
                'billing_type' => $billing->billing_type,
                'billing_amount' => $this->schedule->amount,
                'billing_priority' => $billing->priority,
                'billing_generated_date' => $this->schedule->scheduled_at,
                'payment_amount' => 0,
                'user_id' => $user->id,
                'user_name' => $user->name,
                'user_blok_number' => $user->blok,
                'created_by_user_id' => $this->schedule->created_by_user_id,
                'created_by_user_name' => $createdBy,
            ]);

            // Create log for the user
            $user->logs()->create([
                'organization_id' => $oganizationId,
                'username' => $createdBy,
                'description' => "Tagihan manual '{$billing->name}' sebesar Rp " . number_format($this->schedule->amount, 0, ',', '.') . " telah dibuat pada tanggal " . $this->schedule->scheduled_at->format('d M Y'),
                'url_link' => '/admin/master/tagihan',
            ]);
        }


        $this->schedule->is_generated = true;
        $this->schedule->save();

        // Dispatch pembayaran otomatis deposit job after all billings are created
        Log::info("Dispatching PembayaranOtomatisDepositJob after manual billing creation for schedule ID: {$this->schedule->id}");
        PembayaranOtomatisDepositJob::dispatch(null, $oganizationId);
    }
}
