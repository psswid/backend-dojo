<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Demo user — local personal platform. Change the password after first login.
        User::updateOrCreate(
            ['email' => 'psswiderski@gmail.com'],
            [
                'name' => 'Piotr Świderski',
                'password' => Hash::make('dojo1234'),
            ]
        );

        $this->call(CurriculumSeeder::class);
        $this->call(TaskSeeder::class);
    }
}
