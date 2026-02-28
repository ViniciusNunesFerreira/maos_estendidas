@php
    // Buscar aprovações pendentes
    $pendingApprovals = \App\Models\Filho::where('status', 'pending')->count();
@endphp

<aside class="w-64 bg-white shadow-lg flex flex-col" x-data="{ collapsed: false }">
    <!-- Logo -->
    <div class="h-16 flex items-center justify-between px-6 border-b border-gray-200">
        <div class="flex items-center space-x-3">
            <img src="{{ asset('assets/images/logotipo/logo.png') }}" alt="Mãos Estendidas" class="h-10 w-10">
            <span class="font-bold text-xl text-gray-800" x-show="!collapsed">Mãos Estendidas</span>
        </div>
    </div>
    
    <!-- Navigation Menu -->
    <nav class="flex-1 overflow-y-auto py-4">
        {{-- Dashboard --}}
        <a href="{{ route('admin.dashboard') }}" 
           class="flex items-center px-4 py-2.5 mx-2 mb-1 rounded-lg transition-colors {{ request()->routeIs('admin.dashboard') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}">
            <x-icon name="home" class="w-5 h-5 mr-3" />
            <span class="font-medium">Dashboard</span>
        </a>
        
        {{-- Gestão de Filhos --}}
        <div class="mt-6 px-4">
            <p class="px-2 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">
                Gestão de Filhos
            </p>
            
            <a href="{{ route('admin.filhos.index') }}" 
               class="flex items-center px-2 py-2.5 mb-1 rounded-lg transition-colors {{ request()->routeIs('admin.filhos.index') || request()->routeIs('admin.filhos.show') || request()->routeIs('admin.filhos.edit') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}">
                <x-icon name="users" class="w-5 h-5 mr-3" />
                <span>Lista de Filhos</span>
            </a>
            
            <a href="{{ route('admin.filhos.approval') }}" 
               class="flex items-center justify-between px-2 py-2.5 mb-1 rounded-lg transition-colors {{ request()->routeIs('admin.filhos.approval') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}">
                <div class="flex items-center">
                    <x-icon name="clipboard-document-check" class="w-5 h-5 mr-3" />
                    <span>Aprovações</span>
                </div>
                @if($pendingApprovals > 0)
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-red-100 text-red-800">
                        {{ $pendingApprovals }}
                    </span>
                @endif
            </a>
        </div>
        
        {{-- Produtos --}}
        <div class="mt-6 px-4">
            <p class="px-2 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">
                Produtos
            </p>
            
            <a href="{{ route('admin.products.index') }}" 
               class="flex items-center px-2 py-2.5 mb-1 rounded-lg transition-colors {{ request()->routeIs('admin.products.*') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}">
                <x-icon name="shopping-bag" class="w-5 h-5 mr-3" />
                <span>Todos os Produtos</span>
            </a>
            
            <a href="{{ route('admin.categories.index') }}" 
               class="flex items-center px-2 py-2.5 mb-1 rounded-lg transition-colors {{ request()->routeIs('admin.categories.*') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}">
                <x-icon name="squares-2x2" class="w-5 h-5 mr-3" />
                <span>Categorias</span>
            </a>
            
            <a href="{{ route('admin.stock.index') }}" 
               class="flex items-center px-2 py-2.5 mb-1 rounded-lg transition-colors {{ request()->routeIs('admin.stock.*') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}">
                <x-icon name="cube" class="w-5 h-5 mr-3" />
                <span>Estoque</span>
            </a>
        </div>
        
        {{-- Financeiro --}}
        <div class="mt-6 px-4">
            <p class="px-2 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">
                Financeiro
            </p>
            
            <a href="{{ route('admin.invoices.index') }}" 
               class="flex items-center px-2 py-2.5 mb-1 rounded-lg transition-colors {{ request()->routeIs('admin.invoices.*') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}">
                <x-icon name="document-text" class="w-5 h-5 mr-3" />
                <span>Faturas</span>
            </a>
            
            <a href="{{ route('admin.subscriptions.index') }}" 
               class="flex items-center px-2 py-2.5 mb-1 rounded-lg transition-colors {{ request()->routeIs('admin.subscriptions.*') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}">
                <x-icon name="calendar-days" class="w-5 h-5 mr-3" />
                <span>Assinaturas</span>
            </a>
        </div>
        
        {{-- Pedidos --}}
        <div class="mt-6 px-4">
            <p class="px-2 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">
                Gestão de Pedidos
            </p>

            <a href="{{ route('admin.orders.index') }}" 
               class="flex items-center px-2 py-2.5 mb-1 rounded-lg transition-colors {{ request()->routeIs('admin.orders.*') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}">
                <x-icon name="shopping-cart" class="w-5 h-5 mr-3" />
                <span>Pedidos</span>
            </a>
        </div>

        {{-- Materiais de Estudo --}}
        <div class="mt-6 px-4">
            <p class="px-2 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">
                Arquivos de Aulas
            </p>

            <a href="{{ route('admin.materials.index') }}"
                class="flex items-center px-2 py-2.5 mb-1 rounded-lg transition-colors {{ request()->routeIs('admin.materials.*') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}" >
                <x-icon name="play" class="w-5 h-5 mr-3" />
                <span>Materiais Estudo</span>
            </a>

        </div>
        
        {{-- Relatórios --}}
        <div class="mt-6 px-4">
            <p class="px-2 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">
                Relatórios
            </p>
            
            <a href="{{ route('admin.reports.sales') }}" 
               class="flex items-center px-2 py-2.5 mb-1 rounded-lg transition-colors {{ request()->routeIs('admin.reports.sales') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}">
                <x-icon name="chart-bar" class="w-5 h-5 mr-3" />
                <span>Vendas</span>
            </a>
            
            <a href="{{ route('admin.reports.products') }}" 
               class="flex items-center px-2 py-2.5 mb-1 rounded-lg transition-colors {{ request()->routeIs('admin.reports.products') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}">
                <x-icon name="cube" class="w-5 h-5 mr-3" />
                <span>Produtos</span>
            </a>
            
            <a href="{{ route('admin.reports.financial') }}" 
               class="flex items-center px-2 py-2.5 mb-1 rounded-lg transition-colors {{ request()->routeIs('admin.reports.financial') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}">
                <x-icon name="banknotes" class="w-5 h-5 mr-3" />
                <span>Financeiro</span>
            </a>

            
            
            
        </div>


        
        {{-- Configurações --}}
        <div class="mt-6 px-4">

            <p class="px-2 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">
                Gerenciamento
            </p>

            <a href="{{ route('admin.settings.payment-gateways') }}" 
               class="flex items-center px-2 py-2.5 mb-1 rounded-lg transition-colors {{ request()->routeIs('admin.settings.*') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}">
                <x-icon name="currency-dollar" class="w-5 h-5 mr-3" />
                <span>Pagamentos</span>
            </a>

            <a href="{{ route('admin.settings.index') }}" 
               class="flex items-center px-2 py-2.5 mb-1 rounded-lg transition-colors {{ request()->routeIs('admin.settings.*') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}">
                <x-icon name="cog-6-tooth" class="w-5 h-5 mr-3" />
                <span>Configurações</span>
            </a>

            
        </div>
    </nav>
    
    <!-- User Info -->
    <div class="h-16 border-t border-gray-200 px-4 flex items-center justify-between">
        <div class="flex items-center space-x-3">
            <div class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center">
                <span class="text-white font-semibold text-sm">
                    {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                </span>
            </div>
            <div x-show="!collapsed">
                <p class="text-sm font-medium text-gray-700">{{ auth()->user()->name }}</p>
                <p class="text-xs text-gray-500">{{ ucfirst(auth()->user()->role) }}</p>
            </div>
        </div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="text-gray-500 hover:text-gray-700" title="Sair">
                <x-icon name="x-circle" class="h-5 w-5" />
            </button>
        </form>
    </div>
</aside>