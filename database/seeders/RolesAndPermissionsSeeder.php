<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Resetar cache de permissões
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // =====================================================
        // CRIAR ROLES
        // =====================================================
        
        $roles = [
            [
                'name' => 'admin',
                'guard_name' => 'web',
                
            ],
            [
                'name' => 'manager',
                'guard_name' => 'web',
                
            ],
            [
                'name' => 'operator',
                'guard_name' => 'web',
                
            ],
            [
                'name' => 'filho',
                'guard_name' => 'web',
                
            ],
        ];

        foreach ($roles as $roleData) {
            Role::firstOrCreate(
                ['name' => $roleData['name'], 'guard_name' => $roleData['guard_name']]
                
            );
        }

        $this->command->info('✅ Roles criadas: admin, manager, operator, filho');

        // =====================================================
        // CRIAR PERMISSIONS
        // =====================================================
        
        $permissions = [
            // Gestão de Filhos
            ['name' => 'filhos.view'],
            ['name' => 'filhos.create'],
            ['name' => 'filhos.edit'],
            ['name' => 'filhos.delete'],
            ['name' => 'filhos.approve'],
            ['name' => 'filhos.block'],
            ['name' => 'filhos.credit'],

            // Gestão de Produtos
            ['name' => 'products.view'],
            ['name' => 'products.create'],
            ['name' => 'products.edit'],
            ['name' => 'products.delete'],

            // Gestão de Estoque
            ['name' => 'stock.view'],
            ['name' => 'stock.adjust'],
            ['name' => 'stock.entry'],
            ['name' => 'stock.out'],

            // Gestão de Pedidos/Vendas
            ['name' => 'orders.view'],
            ['name' => 'orders.create'],
            ['name' => 'orders.cancel'],
            ['name' => 'orders.refund'],

            // Gestão de Faturas
            ['name' => 'invoices.view'],
            ['name' => 'invoices.create'],
            ['name' => 'invoices.edit'],
            ['name' => 'invoices.cancel'],
            ['name' => 'invoices.mark-paid'],

            // Gestão de Assinaturas
            ['name' => 'subscriptions.view'],
            ['name' => 'subscriptions.create'],
            ['name' => 'subscriptions.edit'],
            ['name' => 'subscriptions.cancel'],
            ['name' => 'subscriptions.pause'],

            // Relatórios
            ['name' => 'reports.view'],
            ['name' => 'reports.export'],

            // Configurações
            ['name' => 'settings.view'],
            ['name' => 'settings.edit'],

            // Dashboard
            ['name' => 'dashboard.view'],

            // PDV Específico
            ['name' => 'pdv.operate'],
            ['name' => 'pdv.give-discount'],
            ['name' => 'pdv.override-limit'],
        ];

        foreach ($permissions as $permissionData) {
            Permission::firstOrCreate(
                ['name' => $permissionData['name'], 'guard_name' => 'web']
            );
        }

        $this->command->info('✅ ' . count($permissions) . ' permissions criadas');

        // =====================================================
        // ATRIBUIR PERMISSIONS ÀS ROLES
        // =====================================================
        
        // ADMIN - Todas as permissões
        $adminRole = Role::findByName('admin');
        $adminRole->givePermissionTo(Permission::all());
        $this->command->info('✅ Admin: Todas as permissões atribuídas');

        // MANAGER - Gestão completa (exceto algumas sensíveis)
        $managerRole = Role::findByName('manager');
        $managerRole->givePermissionTo([
            // Filhos
            'filhos.view', 'filhos.create', 'filhos.edit', 'filhos.approve', 'filhos.credit',
            // Produtos
            'products.view', 'products.create', 'products.edit', 'products.delete',
            // Estoque
            'stock.view', 'stock.adjust', 'stock.entry', 'stock.out',
            // Pedidos
            'orders.view', 'orders.create', 'orders.cancel',
            // Faturas
            'invoices.view', 'invoices.create', 'invoices.edit', 'invoices.mark-paid',
            // Assinaturas
            'subscriptions.view', 'subscriptions.create', 'subscriptions.edit', 
            'subscriptions.cancel', 'subscriptions.pause',
            // Relatórios
            'reports.view', 'reports.export',
            // Dashboard
            'dashboard.view',
            // PDV
            'pdv.operate', 'pdv.give-discount',
        ]);
        $this->command->info('✅ Manager: Permissões de gestão atribuídas');

        // OPERATOR - Operação do PDV
        $operatorRole = Role::findByName('operator');
        $operatorRole->givePermissionTo([
            // Visualização básica
            'filhos.view',
            'products.view',
            'stock.view',
            // Operação PDV
            'orders.view', 'orders.create',
            'pdv.operate', 'pdv.give-discount',
            // Dashboard básico
            'dashboard.view',
        ]);
        $this->command->info('✅ Operator: Permissões de PDV atribuídas');

        // FILHO - Acesso limitado (app mobile)
        $filhoRole = Role::findByName('filho');
        $filhoRole->givePermissionTo([
            'orders.view', // Apenas seus próprios pedidos
            'invoices.view', // Apenas suas próprias faturas
            'subscriptions.view', // Apenas sua própria assinatura
        ]);
        $this->command->info('✅ Filho: Permissões básicas atribuídas');

        // =====================================================
        // RESUMO
        // =====================================================
        
        $this->command->newLine();
        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->command->info('✅ ROLES E PERMISSIONS CRIADAS COM SUCESSO!');
        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->command->newLine();
        $this->command->table(
            ['Role', 'Permissions Count'],
            [
                ['admin', $adminRole->permissions->count()],
                ['manager', $managerRole->permissions->count()],
                ['operator', $operatorRole->permissions->count()],
                ['filho', $filhoRole->permissions->count()],
            ]
        );
    }
}