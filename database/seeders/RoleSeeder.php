<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //create admin role
        Role::firstOrCreate([
            'name' => 'admin',
        ]);

        //create superadmin role
        Role::firstOrCreate([
            'name' => 'superadmin',
        ]);
    }
}
