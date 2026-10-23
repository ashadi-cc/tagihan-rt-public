<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Organization;
use Illuminate\Support\Str;

class CreateUserService extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:create-user-service';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a service user with API token (Interactive mode)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('=== Create Service User ===');
        $this->newLine();

        // Check if organizations exist
        $organizations = Organization::all();
        
        if ($organizations->isEmpty()) {
            $this->error('No organizations found!');
            $this->error('Please create an organization first using: php artisan organization:create');
            return 1;
        }

        // Let user select organization
        $orgChoices = $organizations->pluck('name', 'id')->toArray();
        $orgId = $this->choice(
            'Select organization',
            $orgChoices,
            $organizations->first()->id
        );
        
        // Get the actual organization ID from the choice
        $selectedOrg = $organizations->firstWhere('name', $orgId);

        // Step 1: Ask for abilities
        $this->info('Enter abilities (comma-separated, e.g., admin:manage, orders:read, orders:write)');
        $abilitiesInput = $this->ask('Abilities', 'admin:manage');
        
        // Parse abilities
        $abilities = array_map('trim', explode(',', $abilitiesInput));

        // Step 5: Confirm before creating
        $this->newLine();
        $this->info('Review the information:');
        $this->table(
            ['Field', 'Value'],
            [
                ['Organization', $selectedOrg->name],
                ['Abilities', implode(', ', $abilities)],
            ]
        );

        $adminUser = User::where('organization_id', $selectedOrg->id)
            ->where('username', 'admin')
            ->first();

        if (!$adminUser) {
            $this->error('Admin user not found in the selected organization.');
            return 1;
        }

        if (!$this->confirm('Do you want to create this service user?', true)) {
            $this->warn('Operation cancelled.');
            return;
        }

        // Create token with abilities
        $token = $adminUser->createToken('service-user-token', $abilities)->plainTextToken;

        $this->newLine();
        $this->info("✓ Service user created successfully!");
        $this->newLine();
        $this->warn("Token: {$token}");
        $this->newLine();
        $this->comment("Please save the token securely. It won't be shown again.");
    }
}
