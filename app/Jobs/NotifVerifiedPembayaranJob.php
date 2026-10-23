<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Pembayaran;
use App\Models\PushMessage;

class NotifVerifiedPembayaranJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $pembayaranId;

    /**
     * Create a new job instance.
     */
    public function __construct($pembayaranId)
    {
        $this->pembayaranId = $pembayaranId;
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

        if ($pembayaran->status !== 'verified') {
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

        $detailBillingMessages = []; 
        foreach ($pembayaran->tagihans as $index => $tagihan) {
            $number = $index + 1;
            $detailBillingMessages[] = $number . ". " . $tagihan->billing_name ." (". $tagihan->period . ") Rp " . number_format($tagihan->payment_amount, 2, ',', '.');
        }

        $orgDetail = $pembayaran->organization ? $pembayaran->organization->description : '';

        $messageContent = "Yth. $user->name Penghuni $user->blok,\n";
        $messageContent .= "Pembayaran Anda sebesar Rp " . number_format($pembayaran->payment_amount, 2, ',', '.') . " pada tanggal " . $pembayaran->payment_date->format('d-m-Y');

        if ($pembayaran->payment_type != 'user_deposit' && count($detailBillingMessages) > 0) {
            $messageContent .=  " untuk:\n" .implode("\n", $detailBillingMessages) . "\n";
            $messageContent .=  "Telah berhasil diverifikasi oleh Bendahara $orgDetail.\n";
        } else {
            $messageContent .=  " telah berhasil diverifikasi oleh Bendahara $orgDetail.\n";
        }

        $messageContent .= "Terima kasih atas pembayaran Anda.";

        // save to push_messages table
        $pushMessage = new PushMessage();
        $pushMessage->organization_id = $pembayaran->organization_id;
        $pushMessage->message_type = 'dm';
        $pushMessage->recipient_id = $user->whatsapp_id;
        $pushMessage->message_content = $messageContent;
        $pushMessage->save();
    }
}
