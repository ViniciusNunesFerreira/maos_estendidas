<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SettingsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function __construct(
        private SettingsService $settingsService
    ) {}

    /**
     * Exibir página de configurações
     * GET /admin/settings
     */
    public function index(): View
    {
        return view('admin.settings.index');
    }

    /**
     * Obter todas as configurações (API)
     * GET /admin/settings/all
     */
    public function all()
    {
        try {
            $settings = $this->settingsService->all();

            return response()->json([
                'success' => true,
                'data' => $settings,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Exportar configurações
     * GET /admin/settings/export
     */
    public function export()
    {
        try {
            $settings = $this->settingsService->export();

            $filename = 'settings_' . date('Y-m-d_His') . '.json';

            return response()->json($settings)
                ->header('Content-Type', 'application/json')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Erro ao exportar configurações: ' . $e->getMessage());
        }
    }

    /**
     * Importar configurações
     * POST /admin/settings/import
     */
    public function import(Request $request)
    {
        try {
            $validated = $request->validate([
                'file' => 'required|file|mimes:json|max:2048',
            ]);

            $content = file_get_contents($validated['file']->getRealPath());
            $settings = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return redirect()
                    ->back()
                    ->with('error', 'Arquivo JSON inválido.');
            }

            $count = $this->settingsService->import($settings);

            return redirect()
                ->back()
                ->with('success', "{$count} configurações importadas com sucesso!");

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Erro ao importar configurações: ' . $e->getMessage());
        }
    }
}