<x-guest-layout class="bg-gray-50">
    
    <!-- Conteneur principal : Centré et largeur contrôlée -->
    <div class="min-h-screen flex items-center justify-center px-4 sm:px-6 lg:px-8">
        
        <div class="max-w-md w-full space-y-8">
            
            <!-- En-tête ePark -->
            <div class="text-center">
                <!-- Logo Texte Simple (Aucune image externe) -->
                <h1 class="text-5xl font-extrabold text-indigo-600 tracking-tight mb-2">
                    ePark
                </h1>
                <p class="text-gray-500 text-sm">
                    Connectez-vous pour gérer vos réservations
                </p>
            </div>

            <!-- Session Status -->
            <x-auth-session-status class="mb-2" :status="session('status')" />

            <!-- Carte du Formulaire (Padding ajusté : py-8 au lieu de py-10) -->
            <div class="bg-white py-8 px-6 shadow-sm rounded-xl border border-gray-200">
                
                <form method="POST" action="{{ route('login') }}" class="space-y-5">
                    @csrf

                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <div class="relative">
                            <!-- Icône Email SVG incluse -->
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
                                value="{{ old('email') }}"
                                class="appearance-none block w-full pl-10 pr-3 py-2.5 border border-gray-300 rounded-lg placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm transition-colors"
                                placeholder="nom@exemple.com"
                            >
                        </div>
                        <!-- Gestion des erreurs Laravel standard -->
                        @error('email')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Mot de passe -->
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label for="password" class="block text-sm font-medium text-gray-700">Mot de passe</label>
                            @if (Route::has('password.request'))
                                <a href="{{ route('password.request') }}" class="text-xs text-indigo-600 hover:text-indigo-500 font-medium">
                                    Oublié ?
                                </a>
                            @endif
                        </div>
                        <div class="relative">
                            <!-- Icône Cadenas SVG incluse -->
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                            </div>
                            <input 
                                id="password" 
                                name="password" 
                                type="password" 
                                autocomplete="current-password" 
                                required 
                                class="appearance-none block w-full pl-10 pr-3 py-2.5 border border-gray-300 rounded-lg placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm transition-colors"
                            >
                        </div>
                        @error('password')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Se souvenir de moi -->
                    <div class="flex items-center">
                        <input id="remember_me" name="remember" type="checkbox" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                        <label for="remember_me" class="ml-2 block text-sm text-gray-700">
                            Se souvenir de moi
                        </label>
                    </div>

                    <!-- Bouton -->
                    <div>
                        <button type="submit" class="group w-full flex justify-center py-2.5 px-4 border border-transparent text-sm font-medium rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200">
                            Connexion
                        </button>
                    </div>

                </form>
            </div>

            <!-- Footer -->
            <div class="text-center text-sm text-gray-500">
                Pas de compte ? 
                @if (Route::has('register'))
                    <a href="{{ route('register') }}" class="font-medium text-indigo-600 hover:text-indigo-500">S'inscrire</a>
                @endif
            </div>

        </div>
    </div>
</x-guest-layout>