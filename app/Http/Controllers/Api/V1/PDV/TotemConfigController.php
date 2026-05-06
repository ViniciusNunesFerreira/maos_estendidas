<?php

namespace App\Http\Controllers\Api\V1\PDV;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Device;

class TotemConfigController extends Controller
{
    public function getConfig(Request $request)
    {
        $deviceId = $request->header('X-Device-ID');
        $device = Device::where('internal_id', $deviceId)->first();

        return response()->json([
            'success' => true,
            'data' => [
                'printer_client_ip' => $device?->printer_client_ip,
                'printer_kitchen_ip' => $device?->printer_kitchen_ip,
            ]
        ]);
    }
}
