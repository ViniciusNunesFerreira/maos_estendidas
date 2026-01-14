<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class CategoryController extends Controller
{
    /**
     * Listar categorias
     */
    public function index(): View
    {
        return view('admin.categories.index');
    }

    /**
     * Formulário de criação
     */
    public function create(): View
    {
        $parentCategories = Category::whereNull('parent_id')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('admin.categories.create', compact('parentCategories'));
    }

    /**
     * Salvar nova categoria
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'parent_id' => 'nullable|uuid|exists:categories,id',
            'icon' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:7',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $validated['slug'] = \Str::slug($validated['name']);
        $validated['is_active'] = $request->boolean('is_active', true);

        Category::create($validated);

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Categoria criada com sucesso!');
    }

    /**
     * Exibir categoria
     */
    public function show(Category $category): View
    {
        $category->load(['products' => fn($q) => $q->limit(10), 'children']);
        $category->loadCount('products');

        return view('admin.categories.show', compact('category'));
    }

    /**
     * Formulário de edição
     */
    public function edit(Category $category): View
    {
        $parentCategories = Category::whereNull('parent_id')
            ->where('id', '!=', $category->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('admin.categories.edit', compact('category', 'parentCategories'));
    }

    /**
     * Atualizar categoria
     */
    public function update(Request $request, Category $category): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'parent_id' => 'nullable|uuid|exists:categories,id',
            'icon' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:7',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        // Evitar auto-referência
        if ($validated['parent_id'] === $category->id) {
            $validated['parent_id'] = null;
        }

        $validated['is_active'] = $request->boolean('is_active', true);

        // Atualizar slug se nome mudou
        if ($validated['name'] !== $category->name) {
            $validated['slug'] = \Str::slug($validated['name']);
        }

        $category->update($validated);

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Categoria atualizada com sucesso!');
    }

    /**
     * Remover categoria
     */
    public function destroy(Category $category): RedirectResponse
    {
        // Verificar produtos vinculados
        if ($category->products()->exists()) {
            return redirect()
                ->route('admin.categories.index')
                ->with('error', 'Não é possível remover categoria com produtos vinculados.');
        }

        // Verificar subcategorias
        if ($category->children()->exists()) {
            return redirect()
                ->route('admin.categories.index')
                ->with('error', 'Não é possível remover categoria com subcategorias.');
        }

        $category->delete();

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Categoria removida com sucesso!');
    }
}