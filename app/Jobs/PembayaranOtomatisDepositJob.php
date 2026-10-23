<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use App\Models\User;
use App\Http\Controllers\Admin\Util;
use Illuminate\Support\Facades\Log;

class PembayaranOtomatisDepositJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Util;

    protected $userId;
    protected $organizationId;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 300;

    /**
     * Create a new job instance.
     *
     * @param int|null $userId
     * @param int|null $organizationId
     */
    public function __construct($userId = null, $organizationId = null)
    {
        $this->userId = $userId;
        $this->organizationId = $organizationId;
    }

    /**
     * Get the middleware the job should pass through.
     */
    public function middleware()
    {
        // If userId is provided, use unique lock per user
        if ($this->userId) {
            return [
                (new WithoutOverlapping("pembayaran-otomatis-deposit-user-{$this->userId}"))
                    ->releaseAfter(120) // Release lock after 2 minutes if job fails
                    ->expireAfter(300) // Expire lock after 5 minutes
            ];
        }

        // If processing all users, use global lock
        return [
            (new WithoutOverlapping('pembayaran-otomatis-deposit-all'))
                ->releaseAfter(180) // Release lock after 3 minutes if job fails
                ->expireAfter(600) // Expire lock after 10 minutes
        ];
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            if ($this->userId) {
                // Process specific user
                $user = User::findOrFail($this->userId);
                
                Log::info("Starting pembayaran otomatis deposit for user: {$user->name} (ID: {$user->id})");
                
                $this->pembayaranOtomatisFromUserDeposit($user);
                
                Log::info("Completed pembayaran otomatis deposit for user: {$user->name} (ID: {$user->id})");
            } else {
                // Process all users (optionally filtered by organization)
                $query = User::query();
                
                if ($this->organizationId) {
                    $query->where('organization_id', $this->organizationId);
                }
                
                $users = $query->get();
                
                Log::info("Starting pembayaran otomatis deposit for {$users->count()} users");
                
                $processedCount = 0;
                foreach ($users as $user) {
                    try {
                        $this->pembayaranOtomatisFromUserDeposit($user);
                        $processedCount++;
                    } catch (\Exception $e) {
                        Log::error("Failed to process pembayaran otomatis deposit for user {$user->id}: " . $e->getMessage());
                        // Continue with next user even if one fails
                        continue;
                    }
                }
                
                Log::info("Completed pembayaran otomatis deposit for {$processedCount} users");
            }
        } catch (\Exception $e) {
            Log::error("Error in PembayaranOtomatisDepositJob: " . $e->getMessage());
            throw $e; // Re-throw to mark job as failed
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("PembayaranOtomatisDepositJob failed: " . $exception->getMessage(), [
            'user_id' => $this->userId,
            'organization_id' => $this->organizationId,
        ]);
    }
}
