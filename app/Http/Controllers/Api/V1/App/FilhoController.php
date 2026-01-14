<?php
// app/Http/Controllers/Api/V1/App/FilhoController.php

namespace App\Http\Controllers\Api\V1\App;

use App\Http\Controllers\Controller;
use App\Http\Resources\FilhoResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FilhoController extends Controller
{
    public function profile(): JsonResponse
    {
        $filho = auth()->user()->filho->load('user');

        return response()->json(new FilhoResource($filho));
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => 'nullable|string|max:20',
            'emergency_contact' => 'nullable|string|max:100',
            'emergency_phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
        ]);

        $filho = auth()->user()->filho;
        $filho->update($request->only([
            'phone',
            'emergency_contact',
            'emergency_phone',
            'address',
        ]));

        return response()->json(new FilhoResource($filho->fresh('user')));
    }
}