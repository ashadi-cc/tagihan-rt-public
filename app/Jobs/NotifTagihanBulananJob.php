<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\User;
use App\Models\Organization;
use App\Models\PushMessage;
use App\Models\Tagihan;
use Illuminate\Queue\Middleware\WithoutOverlapping;

class NotifTagihanBulananJob implements ShouldQueue
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
            (new WithoutOverlapping('notif-tagihan-bulanan'))
                ->releaseAfter(60) // Release lock after 60 seconds if job fails
                ->expireAfter(180) // Expire lock after 3 minutes
        ];
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // periode bulan-tahun saat ini in indonesia format
        $monthYear = now()->locale('id')->translatedFormat('F Y');
        // $users = User::with('organization')->whereNotNull('whatsapp_id')->get();

        // foreach ($users as $user) {
        //     if ($user->whatsapp_id == null || $user->whatsapp_id == '') {
        //         continue;
        //     }

        //     $currentTagihan = $user->getCurrentMonthTagihan();
        //     if ($currentTagihan < 1) {
        //         continue;
        //     }

        //     $organization = $user->organization;

        //     $rupiahFormatter = new \NumberFormatter('id_ID', \NumberFormatter::CURRENCY);
        //     $formattedAmount = $rupiahFormatter->format($currentTagihan);
        //     $siteWebsite = config('app.url');

        //     $messageContent = "Yth. $user->name Penghuni $user->blok $organization->description,\n";
        //     $messageContent .= "Anda mempunyai tagihan sebesar $formattedAmount untuk periode bulan $monthYear.\n";
        //     $messageContent .= "Untuk informasi lebih tentang detail iuran dan konfirmasi pembayaran, silakan kunjungi: $siteWebsite \n";
        //     $messageContent .= "Terima kasih.";

        //     // save to push_messages table
        //     $pushMessage = new PushMessage();
        //     $pushMessage->organization_id = $organization->id;
        //     $pushMessage->message_type = 'dm';
        //     $pushMessage->recipient_id = $user->whatsapp_id;
        //     $pushMessage->message_content = $messageContent;
        //     $pushMessage->save();
        // }


        $organizations = Organization::all();
        $siteURL = config('app.url');
        foreach ($organizations as $organization) {
            $waMemberGrouId = $organization->getWhatsappMemberGroupId();
            if (empty($waMemberGrouId)) {
                \Log::info("NotifTagihanBulananJob: whatsapp_member_group_id is not set for organization {$organization->id}. Skipping group notification.");
                continue;
            }

            $message = "Yth. Penghuni $organization->description,\n";
            $message .= "Tagihan iuran bulan *$monthYear* telah tersedia.\n";

            $rupiahFormatter = new \NumberFormatter('id_ID', \NumberFormatter::CURRENCY);

            $manualTagihans = Tagihan::where('organization_id', $organization->id)
                ->where('billing_type', 'manual')
                ->whereYear('billing_generated_date', now()->year)
                ->whereMonth('billing_generated_date', now()->month)
                ->get()
                ->unique('billing_id');

            if ($manualTagihans->isNotEmpty()) {
                $message .= "Catatan: ada tambahan pembayaran iuran khusus bulan ini sbb:\n";
                foreach ($manualTagihans as $manualTagihan) {
                    $formattedAmount = $rupiahFormatter->format($manualTagihan->billing_amount);
                    $message .= "- $manualTagihan->billing_name: $formattedAmount\n";
                }
            }

            $message .= "Silakan cek dan lakukan pembayaran melalui website: $siteURL\n";
            $message .= "Terima kasih.\n\n";
            $message .= "Butuh bantuan? Chat *pesan pribadi* (japri) ke bot ini untuk:\n";
            $message .= "- Lupa password akun\n";
            $message .= "- Cek tagihan bulan ini/bulan lalu\n\n";
            $message .= "Mohon maaf, bot tidak melayani curhat di luar urusan tagihan dan RT ya.";

            // save to push_messages table
            $pushMessage = new PushMessage();
            $pushMessage->organization_id = $organization->id;
            $pushMessage->message_type = 'group';
            $pushMessage->recipient_id = $waMemberGrouId;
            $pushMessage->message_content = $message;
            $pushMessage->save();
        }
    }
}
