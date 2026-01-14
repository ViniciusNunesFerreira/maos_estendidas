@extends('admin.layouts.guest')

@section('content')
<div x-data="{ showPassword: false, loading: false }">
    <div class="text-center lg:text-left">
        <h2 class="mt-6 text-3xl font-extrabold text-gray-900">
            Bem-vindo de volta
        </h2>
        <p class="mt-2 text-sm text-gray-600">
            Acesse o painel administrativo para gerenciar o PDV e o ecossistema.
        </p>
    </div>

    <div class="mt-8">
        <!-- Mensagens de Erro Flash -->
        @if ($errors->any())
        <div class="mb-4 rounded-md bg-red-50 p-4 border-l-4 border-red-500 animate-pulse">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-red-800">Erro de autenticação</h3>
                    <div class="mt-2 text-sm text-red-700">
                        <ul role="list" class="list-disc pl-5 space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <form action="{{ route('login') }}" method="POST" class="space-y-6" @submit="loading = true">
            @csrf

            <!-- Email -->
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">
                    E-mail Corporativo
                </label>
                <div class="mt-1 relative rounded-md shadow-sm">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                        </svg>
                    </div>
                    <input id="email" name="email" type="email" autocomplete="email" required 
                        class="block w-full pl-10 pr-3 py-3 border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500 sm:text-sm transition duration-150 ease-in-out placeholder-gray-400" 
                        placeholder="seu.nome@email"
                        value="{{ old('email') }}"
                        autofocus>
                </div>
            </div>

            <!-- Senha -->
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700">
                    Senha
                </label>
                <div class="mt-1 relative rounded-md shadow-sm">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </div>
                    <input :type="showPassword ? 'text' : 'password'" id="password" name="password" required
                        class="block w-full pl-10 pr-10 py-3 border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500 sm:text-sm transition duration-150 ease-in-out placeholder-gray-400"
                        placeholder="••••••••">
                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center cursor-pointer" @click="showPassword = !showPassword">
                        <svg x-show="!showPassword" class="h-5 w-5 text-gray-400 hover:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        <svg x-show="showPassword" x-cloak class="h-5 w-5 text-gray-400 hover:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a10.05 10.05 0 011.577-2.92m0-4.99L5.5 5.5m9.9 9.9l1.4 1.4m1.4-1.4l1.4 1.4" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.94 17.94A10.07 10.07 0 0112 19c-4.478 0-8.268-2.943-9.542-7a10.05 10.05 0 012.48-4.04" />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <input id="remember" name="remember" type="checkbox" 
                        class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded transition duration-150 ease-in-out">
                    <label for="remember" class="ml-2 block text-sm text-gray-900">
                        Lembrar-me por 30 dias
                    </label>
                </div>

                <div class="text-sm">
                    <a href="{{ route('password.request') }}" class="font-medium text-primary-600 hover:text-primary-500 transition duration-150 ease-in-out">
                        Esqueceu a senha?
                    </a>
                </div>
            </div>

            <div>
                <button type="submit" 
                    :disabled="loading"
                    class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-all duration-200 transform hover:-translate-y-0.5 disabled:opacity-50 disabled:cursor-not-allowed">
                    
                    <svg x-show="loading" x-cloak class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    
                    <span x-show="!loading">Acessar Painel</span>
                    <span x-show="loading" x-cloak>Autenticando...</span>
                </button>
            </div>
        </form>
        
        <div class="mt-6 text-center text-xs text-gray-500">
             Protegido por reCAPTCHA Enterprise e sujeito à Política de Privacidade.
        </div>
    </div>
</div>
@endsection