<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\BuatTagihanRutinJob;

class BuatTagihanRutinCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tagihan:rutin {dateGenerated? : The date for billing generation (Y-m-d format)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate routine monthly billing (tagihan rutin)';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $dateGenerated = $this->argument('dateGenerated') ?? now()->format('Y-m-d');

        $this->info("Generating routine billing for date: {$dateGenerated}");

        BuatTagihanRutinJob::dispatch($dateGenerated);

        $this->info('Job dispatched successfully!');

        return 0;
    }
}
