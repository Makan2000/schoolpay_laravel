<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'superadmin1@schoolpay.com'],
            [
                'nom' => 'Super Admin',
                'password' => Hash::make('password1230'),
                'role' => 'superadmin',
                'telephone' => '74008589',
            ]
        );
    }
}
