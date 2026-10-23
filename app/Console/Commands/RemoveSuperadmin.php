<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Organization;

class RemoveSuperadmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:remove-superadmin {--orgid=} {--userid=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove the superadmin role from a user';

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
            ->role('superadmin')
            ->get();

        if ($users->isEmpty()) {
            $this->error("No superadmin users found in organization '{$selectedOrg->name}'!");
            return 1;
        }

        $userId = $this->option('userid');

        if ($userId) {
            $selectedUser = $users->firstWhere('id', $userId);

            if (!$selectedUser) {
                $this->error("Superadmin user with ID {$userId} not found in organization '{$selectedOrg->name}'!");
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

        $selectedUser->removeRole('superadmin');

        $this->info("Superadmin role removed from user '{$selectedUser->username}' successfully!");
        $this->info("Organization: {$selectedOrg->name}");

        return 0;
    }
}
