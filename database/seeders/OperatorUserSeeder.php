<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;


class OperatorUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        
        // Criar usuário admin
        $operator = User::firstOrCreate(
            ['email' => 'caixa@maosestendidas.com'],
            [
                'name' => 'Caixa Operador',
                'password' => Hash::make('op1234'),
                'email_verified_at' => now(),
            ]
        );
        
        $operator->assignRole('operator');
        
        $this->command->info('✅ Usuário Operador criado: caixa@maosestendidas / op123');
    }
}
