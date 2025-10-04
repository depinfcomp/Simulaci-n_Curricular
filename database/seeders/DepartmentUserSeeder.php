<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class DepartmentUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
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
