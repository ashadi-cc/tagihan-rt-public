<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Organization;
use Spatie\Permission\Models\Role;

class AssignSuperadmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:assign-superadmin {--orgid=} {--userid=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assign the superadmin role to an existing user';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $organizations = Organization::all();

        if ($organizations->isEmpty()) {
            $this->error('No organizations found!');
            $this->error('Please create an organization first using: php artisan organization:create');
            return 1;
        }

        $orgId = $this->option('orgid');

        if ($orgId) {
            $selectedOrg = $organizations->firstWhere('id', $orgId);

            if (!$selectedOrg) {
                $this->error("Organization with ID {$orgId} not found!");
                return 1;
            }
        } else {
            $orgChoices = $organizations->pluck('name', 'id')->toArray();
            $orgName = $this->choice(
                'Select organization',
                $orgChoices,
                $organizations->first()->id
            );

            $selectedOrg = $organizations->firstWhere('name', $orgName);
        }

        $users = User::where('organization_id', $selectedOrg->id)
            ->role('admin')
            ->get();

        if ($users->isEmpty()) {
            $this->error("No admin users found in organization '{$selectedOrg->name}'!");
            return 1;
        }

        $userId = $this->option('userid');

        if ($userId) {
            $selectedUser = $users->firstWhere('id', $userId);

            if (!$selectedUser) {
                $this->error("Admin user with ID {$userId} not found in organization '{$selectedOrg->name}'!");
                return 1;
            }
        } else {
            $userChoices = $users->mapWithKeys(function ($user) {
                return [$user->id => "{$user->name} ({$user->username})"];
            })->toArray();

            $userLabel = $this->choice('Select user', $userChoices);
            $selectedUserId = array_search($userLabel, $userChoices);
            $selectedUser = $users->firstWhere('id', $selectedUserId);
        }

        if ($selectedUser->hasRole('superadmin')) {
            $this->info("User '{$selectedUser->username}' is already a superadmin.");
            return 0;
        }

        Role::firstOrCreate(['name' => 'superadmin']);
        $selectedUser->assignRole('superadmin');

        $this->info("User '{$selectedUser->username}' assigned as superadmin successfully!");
        $this->info("Organization: {$selectedOrg->name}");

        return 0;
    }
}
