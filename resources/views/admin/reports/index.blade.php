{{-- resources/views/admin/reports/index.blade.php --}}
<x-layouts.admin title="Relatórios">
    <div class="space-y-6">
        {{-- Header --}}
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Relatórios</h1>
                <p class="mt-1 text-sm text-gray-600">
                    Análises e relatórios do sistema
                </p>
            </div>
        </div>

        {{-- Cards de Relatórios --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            {{-- Relatório de Vendas --}}
            <a href="{{ route('admin.reports.sales') }}" class="block bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow p-6">
                <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                        <x-icon name="trending-up" class="w-6 h-6 text-blue-600" />
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Vendas</h3>
                        <p class="text-sm text-gray-500">Análise de vendas por período</p>
                    </div>
                </div>
            </a>

            {{-- Relatório de Produtos --}}
            <a href="{{ route('admin.reports.products') }}" class="block bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow p-6">
                <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                        <x-icon name="package" class="w-6 h-6 text-green-600" />
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Produtos</h3>
                        <p class="text-sm text-gray-500">Desempenho e estoque de produtos</p>
                    </div>
                </div>
            </a>

            {{-- Relatório Financeiro --}}
            <a href="{{ route('admin.reports.financial') }}" class="block bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow p-6">
                <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                        <x-icon name="dollar-sign" class="w-6 h-6 text-purple-600" />
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Financeiro</h3>
                        <p class="text-sm text-gray-500">Receitas, faturas e MRR</p>
                    </div>
                </div>
            </a>
        </div>
    </div>
</x-layouts.admin>