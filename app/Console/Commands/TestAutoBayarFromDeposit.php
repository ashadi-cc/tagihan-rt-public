<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Pembayaran;
use App\Models\Tagihan;
use App\Http\Controllers\Admin\Util;

class TestAutoBayarFromDeposit extends Command
{
    use Util;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:deposit_bayar {userId : The ID of the user to process}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test automatic payment from user deposit for debugging';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userId = $this->argument('userId');

        $user = User::find($userId);
        if (!$user) {
            $this->error("User with ID {$userId} not found!");
            return 1;
        }

        $this->info("Processing user: {$user->name} (ID: {$userId})");
        
        try {
            $this->pembayaranOtomatisFromUserDeposit($user);
            $this->info('Done!');
            return 0;
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }
    }
}
