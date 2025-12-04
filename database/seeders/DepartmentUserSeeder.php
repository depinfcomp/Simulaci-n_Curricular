<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class DepartmentUserSeeder extends Seeder
{
    /**
     * Seeds the users table with a default department administrator account for the UNAL
     * Informatics and Computing Department (Departamento de Informática y Computación).
     * 
     * This seeder creates a default admin user for system access:
     * - Email: depinfcomp_man@unal.edu.co
     * - Password: Depto123 (must be changed on first login)
     * 
     * The seeder uses firstOrCreate to safely handle re-seeding without creating duplicate users.
     * The password is hashed using Laravel's default bcrypt algorithm.
     * 
     * Security note: The 'must_change_password' flag is set to true, forcing the user to set a
     * new secure password on first login. This default password should NEVER be used in production
     * without changing it immediately.
     * 
     * Console output provides login credentials for initial access.
     */
    public function run(): void
    {
        // Create department user if it doesn't exist
        User::firstOrCreate(
            ['email' => 'depinfcomp_man@unal.edu.co'],
            [
                'name' => 'Departamento de Informática y Computación',
                'password' => Hash::make('Depto123'),
                'must_change_password' => true,
            ]
        );

        $this->command->info('Department user created successfully!');
        $this->command->info('Email: depinfcomp_man@unal.edu.co');
        $this->command->info('Password: Depto123');
    }
}
