<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Admin' }} | Mãos Estendidas</title>

        <link rel="icon" href="{{ asset('assets/images/logotipo/logo.png') }}" type="image/x-icon">
    <!-- Additional favicon links for best practice -->
    <link rel="icon" type="image/png" href="{{ asset('assets/images/logotipo/logo.png') }}" sizes="32x32">
    <link rel="apple-touch-icon" href="{{ asset('assets/images/logotipo/logo.png') }}"> 

        <!-- Livewire -->
    @livewireStyles
    
    <!-- TailwindCSS -->
    @vite(['resources/css/admin.css', 'resources/js/admin.js'])
    
    <!-- Alpine.js -->
    <style>
        [x-cloak] { display: none !important; }
        .glass-panel {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
    </style>
</head>
<body class="bg-gray-50 font-sans antialiased">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        @include('admin.layouts.partials.navigation')
        
        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Header -->
            @include('admin.layouts.partials.header', [
                'title' => $title ?? 'Dashboard',
                'breadcrumbs' => $breadcrumbs ?? null
            ])
            
            <!-- Page Content -->
            <main class="flex-1 overflow-y-auto p-6">
                {{ $slot }}
            </main>
        </div>
    </div>
    
    
    
    <!-- Toast Notifications Container -->
    <div id="toast-container" class="fixed bottom-4 right-4 z-50"></div>

     @livewireScripts

    @if (session('success'))
        <script>
            window.addEventListener('DOMContentLoaded', () => {
                window.toast("{{ session('success') }}", 'success');
            });
        </script>
    @endif

    @if (session('error'))
        <script>
            window.addEventListener('DOMContentLoaded', () => {
                window.toast("{{ session('error') }}", 'error');
            });
        </script>
    @endif

    
    @if (session('warning'))
        <script>
            window.addEventListener('DOMContentLoaded', () => {
                window.toast("{{ session('warning') }}", 'warning');
            });
        </script>
    @endif

   

</body>
</html>