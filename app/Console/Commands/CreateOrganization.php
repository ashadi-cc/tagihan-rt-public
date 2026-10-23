<?php

namespace App\Console\Commands;

use App\Models\Organization;
use Illuminate\Console\Command;

class CreateOrganization extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'organization:create {name?} {--description=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new organization';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $name = $this->argument('name') ?? $this->ask('Organization name');
        
        if (empty($name)) {
            $this->error('Organization name is required!');
            return 1;
        }

        $description = $this->option('description') ?? $this->ask('Description (optional)', '');

        $organization = Organization::create([
            'name' => $name,
            'description' => $description,
        ]);

        $this->info("Organization '{$organization->name}' created successfully!");
        $this->line("ID: {$organization->id}");
        return 0;
    }
}
