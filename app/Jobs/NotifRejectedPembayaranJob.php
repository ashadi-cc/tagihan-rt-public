<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Pembayaran;
use App\Models\PushMessage;

class NotifRejectedPembayaranJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $pembayaranId;
    protected $rejectReason;

    /**
     * Create a new job instance.
     */
    public function __construct($pembayaranId, $rejectReason = null)
    {
        $this->pembayaranId = $pembayaranId;
        $this->rejectReason = $rejectReason;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $pembayaran = Pembayaran::find($this->pembayaranId);
        if (!$pembayaran) {
            return;
        }

        if ($pembayaran->status !== 'rejected') {
            return;
        }

        if ($pembayaran->user_id != $pembayaran->created_by_user_id) {
            return;
        }

        $user = $pembayaran->user;
        if (!$user) {
            return;
        }

        if (!$user->whatsapp_id) {
            return;
        }

        $orgDetail = $pembayaran->organization ? $pembayaran->organization->description : '';

        $messageContent = "Yth. $user->name Penghuni $user->blok,\n";
        $messageContent .= "Mohon maaf, pembayaran Anda sebesar Rp " . number_format($pembayaran->payment_amount, 2, ',', '.') . " pada tanggal " . $pembayaran->payment_date->format('d-m-Y') . " belum dapat kami verifikasi oleh Bendahara $orgDetail.\n";

        if (!empty($this->rejectReason)) {
            $messageContent .= "Alasan Penolakan: " . $this->rejectReason . "\n";
        }

        $messageContent .= "Untuk informasi lebih lanjut, mohon menghubungi Bendahara $orgDetail. Terima kasih atas pengertian Anda.";

        // save to push_messages table
        $pushMessage = new PushMessage();
        $pushMessage->organization_id = $pembayaran->organization_id;
        $pushMessage->message_type = 'dm';
        $pushMessage->recipient_id = $user->whatsapp_id;
        $pushMessage->message_content = $messageContent;
        $pushMessage->save();
    }
}
