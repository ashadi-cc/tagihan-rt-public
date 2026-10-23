<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Organization;
use Hash; 

class AdminUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:create';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'create an admin';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Creating admin user...');

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

        $username = $this->ask('Enter username', 'admin');
        $name = $this->ask('Enter full name', 'Admin User');
        $email = $this->ask('Enter email', 'admin@example.com');
        $blok = $this->ask('Enter blok', 'admin');
        $password = $this->secret('Enter password (hidden)');
        
        if (empty($password)) {
            $this->error('Password cannot be empty!');
            return 1;
        }

        $passwordConfirmation = $this->secret('Confirm password');

        if ($password !== $passwordConfirmation) {
            $this->error('Passwords do not match!');
            return 1;
        }

        $user = User::firstOrNew([
            'username' => $username,
        ]); 

        $user->username = $username;
        $user->blok = $blok;
        $user->name = $name;
        $user->email = $email; 
        $user->password = Hash::make($password); 
        $user->default_password = '';
        $user->organization_id = $selectedOrg->id;
        $user->save(); 

        //assign admin role 
        $user->assignRole('admin'); 

        $this->info("Admin user '{$username}' created successfully!");
        $this->info("Organization: {$selectedOrg->name}");
        
        return 0;
    }
}
