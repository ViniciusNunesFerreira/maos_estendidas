@extends('admin.layouts.guest')
@section('content')

    
    <div x-data="{ showPassword: false, loading: false }">

        <div class="text-center lg:text-left">
            <h2 class="mt-6 text-3xl font-extrabold text-gray-900">
                 Esqueceu sua senha?
            </h2>
            <p class="mt-2 text-sm text-gray-600">
                Informe seu email para enviarmos um link de recuperação.
            </p>
        </div>

        <div class="mt-8">

            @if (session('status'))
                <div class="bg-success-50 border-l-4 border-success-500 p-4 mb-4 animate-pulse">
                    <p class="text-sm text-success-700">{{ session('status') }}</p>
                </div>
            @endif

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

            <form class="space-y-6" action="{{ route('password.email') }}" method="POST" @submit="loading = true">
                @csrf
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">Endereço de Email</label>
                    <input id="email" name="email" type="email" autocomplete="email" required 
                        class="mt-1 block w-full px-4 py-3 rounded-lg border-gray-300 shadow-sm input-focus @error('email') border-danger-500 @enderror"
                        placeholder="exemplo@casalar.com" value="{{ old('email') }}">
                    @error('email')
                        <p class="mt-2 text-xs text-danger-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex flex-col space-y-4">
                    <button type="submit" 
                        class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-colors">
                        Enviar Link de Recuperação
                    </button>
                    
                    <a href="{{ route('login') }}" class="text-center text-sm font-medium text-primary-600 hover:text-primary-500">
                        Voltar para o login
                    </a>
                </div>
            </form>

        </div>
    </div>


@endsection




