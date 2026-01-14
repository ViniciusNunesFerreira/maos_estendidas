<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-gray-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="icon" href="{{ asset('assets/images/logotipo/logo.png') }}" type="image/x-icon">
    <!-- Additional favicon links for best practice -->
    <link rel="icon" type="image/png" href="{{ asset('assets/images/logotipo/logo.png') }}" sizes="32x32">
    <link rel="apple-touch-icon" href="{{ asset('assets/images/logotipo/logo.png') }}"> 

    <title>{{ config('app.name', 'Mãos Estendidas') }} - Acesso Administrativo</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">


    <!-- TailwindCSS -->
    @vite(['resources/css/admin.css', 'resources/js/admin.js'])

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        [x-cloak] { display: none !important; }
        .glass-panel {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
    </style>
</head>
<body class="h-full antialiased text-gray-900">
    <div class="min-h-screen flex">
        <!-- Lado Esquerdo - Branding/Art -->
        <div class="hidden lg:flex lg:w-1/2 relative bg-gray-900 overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-br from-primary-600 to-blue-900 opacity-90"></div>
            <!-- Background Pattern opcional -->
            <div class="absolute inset-0 opacity-20" style="background-image: url('data:image/svg+xml,...');"></div>
            
            <div class="relative z-10 w-full flex flex-col justify-between p-12 text-white">
                <div class="flex flex-row items-center">
                    <div class="h-32 w-32  flex items-center justify-center text-primary-600  text-xl mb-4 p-2">
                        <img src="{{ asset('assets/images/logotipo/logo.png')}}" alt="">
                    </div>
                    <div class="shrink mx-4">
                        <h1 class="text-4xl font-bold tracking-tight">Mãos Estendidas</h1>
                        <p class="mt-2 text-primary-100 text-lg">Sistema de Gestão & PDV Integrado</p>
                    </div>
                </div>
                
                <div class="space-y-4">
                    <blockquote class="text-lg font-medium italic text-gray-200">
                        "Tecnologia e cuidado andando juntos para transformar vidas."
                    </blockquote>
                    <p class="text-sm text-gray-400">© {{ date('Y') }} Mãos Estendidas. Todos os direitos reservados.</p>
                </div>
            </div>
        </div>

        <!-- Lado Direito - Form -->
        <div class="flex-1 flex flex-col justify-center py-12 px-4 sm:px-6 lg:flex-none lg:w-1/2 bg-white">
            <div class="mx-auto w-full max-w-sm lg:w-96">
                @yield('content')
            </div>
        </div>
    </div>
</body>
</html>