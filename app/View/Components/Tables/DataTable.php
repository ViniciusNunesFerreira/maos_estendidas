<?php
// app/View/Components/Tables/DataTable.php

namespace App\View\Components\Tables;

use Illuminate\View\Component;
use Illuminate\View\View;
use Illuminate\Support\Collection;

/**
 * DataTable Component
 * 
 * Tabela de dados com listagem, paginação e estados vazios
 * Usado para exibir Products, Filhos, Orders, etc.
 * 
 * @package App\View\Components\Tables
 */
class DataTable extends Component
{
    /**
     * Colunas da tabela
     * 
     * @var array<string>
     */
    public array $columns;

    /**
     * Dados da tabela (Collection ou array)
     * 
     * @var Collection|array
     */
    public $data;

    /**
     * Mensagem quando não há dados
     */
    public string $emptyMessage;

    /**
     * Se aplica estilo zebrado (linhas alternadas)
     */
    public bool $striped;

    /**
     * Se aplica hover nas linhas
     */
    public bool $hoverable;

    /**
     * Create a new component instance.
     */
    public function __construct(
        array $columns,
        $data,
        string $emptyMessage = 'Nenhum registro encontrado',
        bool $striped = true,
        bool $hoverable = true
    ) {
        $this->columns = $columns;
        $this->data = $data;
        $this->emptyMessage = $emptyMessage;
        $this->striped = $striped;
        $this->hoverable = $hoverable;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View
    {
        return view('components.tables.data-table');
    }

    /**
     * Verifica se os dados estão vazios
     */
    public function isEmpty(): bool
    {
        if ($this->data instanceof Collection) {
            return $this->data->isEmpty();
        }
        
        return empty($this->data);
    }

    /**
     * Conta o número de colunas
     */
    public function getColumnCount(): int
    {
        return count($this->columns);
    }
}