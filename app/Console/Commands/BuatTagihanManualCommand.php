<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ManualBillingSchedule;
use App\Jobs\BuatTagihanManualJob;

class BuatTagihanManualCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tagihan:manual';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process manual billing schedules for today';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info("Processing manual billing schedules for: " . now()->format('Y-m-d'));

        $list = ManualBillingSchedule::where('scheduled_at', now()->format('Y-m-d'))
                                    ->where('is_generated', false)
                                    ->get();

        if ($list->isEmpty()) {
            $this->warn('No manual billing schedules found for today.');
            return 0;
        }

        $this->info("Found {$list->count()} schedule(s) to process.");

        foreach ($list as $item) {
            BuatTagihanManualJob::dispatch($item);
            $this->info("Dispatched job for schedule ID: {$item->id}");
        }

        $this->info('All jobs dispatched successfully!');

        return 0;
    }
}
