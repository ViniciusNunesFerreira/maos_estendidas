<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
                 
        // Criar usuário admin
        $admin = User::firstOrCreate(
            ['email' => 'admin@maosestendidas.com'],
            [
                'name' => 'Administrador',
                'password' => Hash::make('admin123'),
                'email_verified_at' => now(),
            ]
        );
        
        $admin->assignRole('admin');
        
        $this->command->info('✅ Usuário admin criado: admin@maosestendidas / admin123');
    }
}
