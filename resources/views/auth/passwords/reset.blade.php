<!DOCTYPE html>
<html lang="pt-BR" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redefinir Senha - Casa Lar</title>
    @vite(['resources/css/admin.css', 'resources/js/admin.js'])
</head>
<body class="h-full flex items-center justify-center p-6">
    <div class="max-w-md w-full space-y-8 bg-white p-10 rounded-2xl shadow-xl border border-gray-100">
        <div>
            <h2 class="text-center text-3xl font-extrabold text-gray-900 tracking-tight">
                Nova Senha
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                Defina sua nova credencial de acesso.
            </p>
        </div>

        <form class="mt-8 space-y-6" action="{{ route('password.update') }}" method="POST">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <div class="space-y-4">
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">Confirme seu Email</label>
                    <input id="email" name="email" type="email" required readonly
                        class="mt-1 block w-full px-4 py-3 bg-gray-50 border-gray-300 rounded-lg text-gray-500 cursor-not-allowed shadow-sm"
                        value="{{ $email ?? old('email') }}">
                    @error('email')
                        <p class="mt-2 text-xs text-danger-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">Nova Senha</label>
                    <input id="password" name="password" type="password" required 
                        class="mt-1 block w-full px-4 py-3 rounded-lg border-gray-300 shadow-sm input-focus @error('password') border-danger-500 @enderror">
                    @error('password')
                        <p class="mt-2 text-xs text-danger-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password-confirm" class="block text-sm font-medium text-gray-700">Confirme a Nova Senha</label>
                    <input id="password-confirm" name="password_confirmation" type="password" required 
                        class="mt-1 block w-full px-4 py-3 rounded-lg border-gray-300 shadow-sm input-focus">
                </div>
            </div>

            <button type="submit" 
                class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-colors">
                Redefinir Senha
            </button>
        </form>
    </div>
</body>
</html>