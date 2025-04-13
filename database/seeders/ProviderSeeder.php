<?php

namespace Database\Seeders;

use App\Models\Provider;
use Illuminate\Database\Seeder;

class ProviderSeeder extends Seeder
{
    private array $insurers = [
        [ 'name' => 'INS-A'],
        [ 'name' => 'INS-B'],
        [ 'name' => 'INS-C'],
        [ 'name' => 'INS-D'],
    ];
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ($this->insurers as $insurer) {
            Provider::factory()->create([
                'name' => $insurer['name'],
            ]);
        }
    }
}
