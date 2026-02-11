<x-guest-layout class="bg-gradient-to-br from-indigo-50 via-white to-slate-50">
    <div class="min-h-screen flex items-center justify-center px-4 py-8">
        <div class="w-full max-w-md">
            <div class="text-center mb-6">
                <div class="mx-auto w-12 h-12 bg-indigo-600 rounded-2xl flex items-center justify-center shadow-lg">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                    </svg>
                </div>
                <h1 class="mt-4 text-2xl font-bold text-gray-900">Mot de passe oublié</h1>
                <p class="mt-1 text-sm text-gray-500">Indiquez votre adresse email et nous vous enverrons un lien pour réinitialiser votre mot de passe.</p>
            </div>

            <x-auth-session-status class="mb-3" :status="session('status')" />

            <div class="bg-white/95 border border-gray-200 shadow-lg rounded-2xl p-5">
                <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
                    @csrf

                    <div>
                        <label for="email" class="block text-sm font-bold text-gray-900 mb-1">Email</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <input
                                id="email"
                                name="email"
                                type="email"
                                autocomplete="email"
                                required
                                autofocus
                                value="{{ old('email') }}"
                                class="appearance-none block w-full pl-10 pr-3 py-2.5 border-2 border-gray-200 bg-gray-50 rounded-xl placeholder-gray-400 focus:outline-none focus:border-indigo-500 focus:ring-0 text-sm font-medium text-gray-900 hover:border-gray-300 transition-all"
                                placeholder="nom@exemple.com"
                            >
                        </div>
                        @error('email')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit" class="w-full inline-flex items-center justify-center py-2.5 px-4 rounded-xl text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 shadow-md shadow-indigo-500/20 transition-colors">
                        Envoyer le lien
                    </button>
                </form>
            </div>

            <p class="mt-5 text-center text-sm text-gray-500">
                Retour à la
                <a href="{{ route('login') }}" class="font-semibold text-indigo-600 hover:text-indigo-500">connexion</a>
            </p>
        </div>
    </div>
</x-guest-layout>
