<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\Company;
use App\Models\Employee;
use App\Models\Responsibility;
use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()
            ->count(10)
            ->has(
                Company::factory()
                    ->count(2)
                    ->has(
                        Team::factory()
                            ->count(5)
                    )
                    ->has(
                        Role::factory()
                            ->count(15)
                            ->has(
                                Responsibility::factory()
                                    ->count(4)
                            )
                    )
            )
            ->create();

        Employee::factory()
            ->count(200)
            ->create();
    }
}
