<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Log;
use App\Models\Organization;

class UpdateLogsOrganization extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'logs:update-organization {org-id : The organization ID to assign to all logs}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update all existing logs with a specific organization ID';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $orgId = $this->argument('org-id');

        // Verify organization exists
        $organization = Organization::find($orgId);
        if (!$organization) {
            $this->error("Organization with ID {$orgId} not found!");
            return 1;
        }

        $this->info("Updating logs with organization ID: {$orgId} ({$organization->name})");

        // Count logs to update
        $logsCount = Log::whereNull('organization_id')->count();
        
        if ($logsCount === 0) {
            $this->info('No logs found that need updating.');
            return 0;
        }

        $this->info("Found {$logsCount} logs to update.");

        if ($this->confirm('Do you want to proceed?', true)) {
            $bar = $this->output->createProgressBar($logsCount);
            $bar->start();

            Log::whereNull('organization_id')->chunk(100, function ($logs) use ($orgId, $bar) {
                foreach ($logs as $log) {
                    $log->organization_id = $orgId;
                    $log->save();
                    $bar->advance();
                }
            });

            $bar->finish();
            $this->newLine();
            $this->info('Logs updated successfully!');

            return 0;
        } else {
            $this->info('Operation cancelled.');
            return 0;
        }
    }
}
