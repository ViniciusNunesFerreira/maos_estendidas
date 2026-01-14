<?php
// app/Http/Controllers/WelcomeController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class WelcomeController extends Controller
{
    public function index()
    {
        return view('welcome', [
            'institutionName' => config('casalar.institution_name', 'Mãos Estendidas'),
        ]);
    }
}