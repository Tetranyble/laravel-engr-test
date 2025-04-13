<?php

namespace Database\Seeders;

use App\Models\Insurer;
use Illuminate\Database\Seeder;

class InsurerSeeder extends Seeder
{
    private array $insurers = [
        ['name' => 'Insurer A', 'code' => 'INS-A'],
        ['name' => 'Insurer B', 'code' => 'INS-B'],
        ['name' => 'Insurer C', 'code' => 'INS-C'],
        ['name' => 'Insurer D', 'code' => 'INS-D'],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ($this->insurers as $insurer) {
            Insurer::factory()->create([
                'name' => $insurer['name'],
                'code' => $insurer['code'],
            ]);
        }
    }
}
