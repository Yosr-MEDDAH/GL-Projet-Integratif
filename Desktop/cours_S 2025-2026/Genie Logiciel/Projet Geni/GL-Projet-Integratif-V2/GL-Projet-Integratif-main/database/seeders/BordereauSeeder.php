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
        Bordereau::create([
            'date_sent' => now(),
            'folder' => 'Folder1',
            'status' => 'En cours',
            'nature' => '3WM',
            'reference' => 'REF123',
        ]);
    }
}
