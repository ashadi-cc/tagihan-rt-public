<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\PushMessage;

class UserRegristationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:user:register {username? : Filter by specific username}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Push notification to user for registration';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $query = User::with('organization')->whereNull('whatsapp_id')->warga();
        
        if ($username = $this->argument('username')) {
            $query->where('username', $username);
        }
        
        $users = $query->get();

        foreach ($users as $user) {
            if (empty($user->phone_number)) {
                continue;
            }

            $orgDesc = $user->organization->description;
            $message = "Yth $user->name $user->blok.";
            $message .= " Saya Asisten Digital $orgDesc akan membantu anda untuk mendapatkan akses sistem iuran online warga. Silahkan balas pesan ini dengan pesan:\n\n";
            $message .= "{$user->blok_name}{$user->blok_number} {$user->phone_number}";

            $this->info("Creating push message for user ID {$user->id}.");

            PushMessage::create([
                'organization_id' => $user->organization_id,
                'message_type' => 'dm',
                'recipient_id' => $user->phone_number,
                'message_content' => $message,
            ]);
        }
    }
}
