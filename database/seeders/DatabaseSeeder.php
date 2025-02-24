<?php

namespace Database\Seeders;

use App\Models\AirtimeTopupPercentage;
use App\Models\Network;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        // AirtimeTopupPercentage::factory()->count(4)->create([
        //     'network_id' => '1',
        //     'network_percentage' => '2'

        // ]);

        Network::create([
            'network_name' => 'MTN',
            'network_id' => '1'
        ]);
    }
}
