<?php

namespace Database\Seeders;

use App\Models\User;
use Database\Seeders\Budget\BudgetPrevisionSeeder;
use Database\Seeders\Budget\CompteSeeder;
use Database\Seeders\Budget\ProjetInvestissementSeeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run()
    {
        User::firstOrCreate(
            ['email' => 'admin@uagl.local'],
            [
                'name' => 'Administrateur UAGL',
                'password' => Hash::make('password'),
            ],
        );

        $this->call([
            GFSeeder::class,
            NRSeeder::class,
            UniteSeeder::class,
            PosteSeeder::class,
            AgentSeeder::class,
            AvancementSeeder::class,
            UpdateAgentLocationsSeeder::class,
            NoteAppreciationSeeder::class,
            PromotionAndAvancementSeeder::class,
            CompteSeeder::class,
            BudgetPrevisionSeeder::class,
            ProjetInvestissementSeeder::class,
        ]);
    }
}