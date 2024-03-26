<?php

namespace Database\Seeders;

use App\Models\Bordereau;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BordereauSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Bordereau::factory()->count(10)->create([
            'status' => 'En cours', // Vous pouvez également utiliser 'Archivé' ici si nécessaire
        ]);
    }
}
