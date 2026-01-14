{{-- resources/views/components/tables/data-table.blade.php --}}
{{-- 
    DataTable Component
    Tabela de dados profissional com suporte a listagens
    
    Uso:
    <x-tables.data-table 
        :columns="['Nome', 'CPF', 'Status', 'Ações']"
        :data="$filhos">
        @foreach($filhos as $filho)
            <td class="px-6 py-4 whitespace-nowrap">{{ $filho->name }}</td>
            <td class="px-6 py-4 whitespace-nowrap">{{ $filho->cpf }}</td>
            <td class="px-6 py-4 whitespace-nowrap">
                <x-badge :variant="$filho->status_color">{{ $filho->status }}</x-badge>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right">
                <x-button size="sm" :href="route('admin.filhos.edit', $filho)">
                    Editar
                </x-button>
            </td>
        @endforeach
    </x-tables.data-table>
--}}

<div {{ $attributes->merge(['class' => 'bg-white rounded-lg shadow overflow-hidden']) }}>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    @foreach($columns as $column)
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            {{ $column }}
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @if($isEmpty())
                    {{-- Estado Vazio --}}
                    <tr>
                        <td colspan="{{ $getColumnCount() }}" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center justify-center">
                                <x-icon name="inbox" class="h-12 w-12 text-gray-400 mb-4" />
                                <p class="text-gray-500 text-sm">{{ $emptyMessage }}</p>
                            </div>
                        </td>
                    </tr>
                @else
                    {{-- Dados da Tabela --}}
                    @foreach($data as $index => $row)
                        <tr class="{{ $hoverable ? 'hover:bg-gray-50' : '' }} {{ $striped && $index % 2 ? 'bg-gray-50' : '' }} transition-colors">
                            {{ $slot }}
                        </tr>
                    @endforeach
                @endif
            </tbody>
        </table>
    </div>
</div>