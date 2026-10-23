<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Pembayaran;
use App\Models\PushMessage;
use Illuminate\Queue\Middleware\WithoutOverlapping;

class NotifPendingPembayaranJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Get the middleware the job should pass through.
     */
    public function middleware()
    {
        return [
            (new WithoutOverlapping('notif-pending-pembayaran'))
                ->releaseAfter(60) // Release lock after 60 seconds if job fails
                ->expireAfter(180) // Expire lock after 3 minutes
        ];
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // get first pembayaran with status 'pending'
        $pembayaran = Pembayaran::where('status', 'pending')->where('is_sent_pushed', false)->first();
        if ($pembayaran) {
            $organization = $pembayaran->organization;
            $waAdminGroupId = $organization->getWhatsappAdminGroupId();

            if (empty($waAdminGroupId)) {
                return;
            }

            $totalPending = Pembayaran::where('organization_id', $pembayaran->organization_id)
                                   ->where('status', 'pending')
                                   ->count();

            $messageContent = "Yth. Bendahara $organization->description,\n";
            $messageContent .= "Terdapat $totalPending pembayaran baru yang perlu ditinjau dari warga.\n";
            $messageContent .= "Terima kasih.";

            // save to push_messages table
            $pushMessage = new PushMessage();
            $pushMessage->organization_id = $organization->id;
            $pushMessage->message_type = 'group';
            $pushMessage->recipient_id = $waAdminGroupId;
            $pushMessage->message_content = $messageContent;
            $pushMessage->save();

            // mark pembayaran as is_sent_pushed = true for all pending pembayaran in the organization
            Pembayaran::where('organization_id', $pembayaran->organization_id)
                      ->where('status', 'pending')
                      ->update(['is_sent_pushed' => true]);
        }
    }
}
