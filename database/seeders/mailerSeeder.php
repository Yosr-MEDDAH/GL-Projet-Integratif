<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class mailerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('mailers')->insert([
            'transport' => 'smtp',
            'host' => 'sandbox.smtp.mailtrap.io',
            'port' => 2525,
            'encryption' => 'tls',
            'username' => '378d47aed4598f',
            'password' => 'f6ad1c1829385f',
            'timeout' => null,
            'local_domain' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
