<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        User::create([
            'first_name' => 'Admin',
            'last_name' => 'User',
            'departamento_id' => 3,
            'email' => 'admin@example.com',
            'username' => 'admin',
            'password' => Hash::make('password1A?'), 
            'is_admin' => true,
        ]);
    }
}



