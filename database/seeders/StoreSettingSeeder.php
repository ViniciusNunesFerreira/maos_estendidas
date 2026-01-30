<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StoreSettingSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('store_settings')->updateOrInsert(
            ['id' => 1], // Garante que sempre alteramos o ID 1
            [
                'is_enabled' => false, // Travado por padrão em produção
                'opening_hours' => json_encode([
                    'mon' => ['08:00', '23:00'],
                    'tue' => ['08:00', '23:00'],
                    'wed' => ['08:00', '23:00'],
                    'thu' => ['08:00', '23:00'],
                    'fri' => ['08:00', '23:00'],
                    'sat' => ['08:00', '23:00'],
                    'sun' => null // Fechado
                ]),
                'maintenance_message' => 'Esta função está inativa para não gerar faturamentos. Disponível em breve!',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}