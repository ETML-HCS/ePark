<x-guest-layout>
    <div class="w-full max-w-3xl animate-fade-in-up">
        <!-- Card principale avec effet de profondeur -->
        <div class="group relative overflow-hidden rounded-3xl border border-slate-200/60 bg-white shadow-2xl shadow-slate-200/50 backdrop-blur-sm transition-all duration-500 hover:shadow-slate-300/60">
            
            <!-- Effet de gradient animé en arrière-plan -->
            <div class="absolute inset-0 bg-gradient-to-br from-emerald-50/40 via-transparent to-blue-50/40 opacity-0 transition-opacity duration-700 group-hover:opacity-100"></div>
            
            <!-- Header avec pattern subtil -->
            <div class="relative border-b border-slate-100 bg-gradient-to-r from-slate-50 via-white to-slate-50 px-5 py-6 sm:px-8">
                <!-- Pattern de points décoratif -->
                <div class="absolute right-0 top-0 h-full w-32 opacity-[0.03]">
                    <svg class="h-full w-full" viewBox="0 0 100 100" preserveAspectRatio="none">
                        <pattern id="dots" x="0" y="0" width="20" height="20" patternUnits="userSpaceOnUse">
                            <circle cx="2" cy="2" r="1" fill="currentColor"/>
                        </pattern>
                        <rect width="100" height="100" fill="url(#dots)"/>
                    </svg>
                </div>

                <div class="relative">
                    <!-- Badge avec animation -->
                    <div class="inline-flex items-center gap-2 rounded-full bg-emerald-100/80 px-3 py-1 backdrop-blur-sm">
                        <span class="relative flex h-2 w-2">
                            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-500 opacity-75"></span>
                            <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-600"></span>
                        </span>
                        <p class="text-[11px] font-black uppercase tracking-[0.2em] text-emerald-800">ePark</p>
                    </div>
                    
                    <h1 class="mt-3 text-3xl font-black tracking-tight text-slate-900 sm:text-4xl">
                        Créer votre compte
                    </h1>
                    <p class="mt-2.5 max-w-lg text-sm leading-relaxed text-slate-600">
                        Accédez aux places disponibles et commencez à réserver en quelques clics. 
                        <span class="hidden sm:inline">Rejoignez notre communauté de conducteurs intelligents.</span>
                    </p>
                </div>
            </div>

            <!-- Corps du formulaire -->
            <div class="relative px-5 py-6 sm:px-8 sm:py-7">
                <form method="POST" action="{{ route('register') }}" class="space-y-5" x-data="{ 
                    step: 1, 
                    siteSelected: '{{ old('favorite_site_id') }}',
                    showPassword: false,
                    strength: 0,
                    checkStrength(password) {
                        let score = 0;
                        if (password.length > 6) score++;
                        if (password.length > 10) score++;
                        if (/[A-Z]/.test(password)) score++;
                        if (/[0-9]/.test(password)) score++;
                        if (/[^A-Za-z0-9]/.test(password)) score++;
                        this.strength = score;
                    }
                }">
                    @csrf

                    <!-- Indicateur d'étapes -->
                    <div class="mb-6 flex items-center justify-center gap-2 sm:justify-start">
                        <div class="flex items-center gap-2">
                            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-emerald-600 text-sm font-bold text-white shadow-lg shadow-emerald-200">1</div>
                            <div class="hidden h-1 w-12 rounded-full bg-emerald-600 sm:block"></div>
                        </div>
                        <div class="flex items-center gap-2 opacity-50">
                            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-slate-200 text-sm font-bold text-slate-600">2</div>
                            <div class="hidden h-1 w-12 rounded-full bg-slate-200 sm:block"></div>
                        </div>
                        <div class="flex items-center gap-2 opacity-50">
                            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-slate-200 text-sm font-bold text-slate-600">3</div>
                        </div>
                    </div>

                    <!-- Section 1: Informations personnelles -->
                    <div class="space-y-4">
                        <h3 class="flex items-center gap-2 text-sm font-bold uppercase tracking-wider text-slate-900">
                            <svg class="h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            Identité
                        </h3>
                        
                        <div class="grid gap-4 sm:grid-cols-2">
                            <!-- Nom avec icône -->
                            <div class="group/input relative">
                                <x-input-label for="name" :value="__('Nom complet')" class="text-xs font-semibold uppercase tracking-wider text-slate-500" />
                                <div class="relative mt-2">
                                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                        <svg class="h-5 w-5 text-slate-400 transition-colors group-focus-within/input:text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                    </div>
                                    <x-text-input id="name" 
                                        class="block w-full rounded-xl border-slate-200 bg-slate-50/50 pl-10 transition-all focus:border-emerald-500 focus:bg-white focus:ring-emerald-500/20" 
                                        type="text" 
                                        name="name" 
                                        :value="old('name')" 
                                        required 
                                        autofocus 
                                        autocomplete="name"
                                        placeholder="Jean Dupont" />
                                </div>
                                <x-input-error :messages="$errors->get('name')" class="mt-2" />
                            </div>

                            <!-- Email avec icône -->
                            <div class="group/input relative">
                                <x-input-label for="email" :value="__('Email')" class="text-xs font-semibold uppercase tracking-wider text-slate-500" />
                                <div class="relative mt-2">
                                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                        <svg class="h-5 w-5 text-slate-400 transition-colors group-focus-within/input:text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                                        </svg>
                                    </div>
                                    <x-text-input id="email" 
                                        class="block w-full rounded-xl border-slate-200 bg-slate-50/50 pl-10 transition-all focus:border-emerald-500 focus:bg-white focus:ring-emerald-500/20" 
                                        type="email" 
                                        name="email" 
                                        :value="old('email')" 
                                        required 
                                        autocomplete="username"
                                        placeholder="jean@exemple.fr" />
                                </div>
                                <x-input-error :messages="$errors->get('email')" class="mt-2" />
                            </div>
                        </div>
                    </div>

                    <!-- Section 2: Site favori -->
                    <div class="space-y-4 pt-3">
                        <h3 class="flex items-center gap-2 text-sm font-bold uppercase tracking-wider text-slate-900">
                            <svg class="h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            Localisation
                        </h3>

                        <div class="relative">
                            <x-input-label for="favorite_site_id" :value="__('Site favori')" class="text-xs font-semibold uppercase tracking-wider text-slate-500" />
                            
                            <div class="relative mt-2">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                    <svg class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                </div>
                                <select id="favorite_site_id" 
                                    name="favorite_site_id" 
                                    x-model="siteSelected"
                                    class="block w-full appearance-none rounded-xl border-2 border-slate-200 bg-slate-50/50 py-2.5 pl-10 pr-10 text-sm font-medium text-slate-900 transition-all hover:border-emerald-300 focus:border-emerald-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-emerald-500/10"
                                    required>
                                    <option value="" disabled>Sélectionner un site</option>
                                    @foreach($sites as $site)
                                        <option value="{{ $site->id }}" {{ old('favorite_site_id') == $site->id ? 'selected' : '' }}>
                                            {{ $site->nom }} — {{ $site->adresse }}
                                        </option>
                                    @endforeach
                                    <option value="other" {{ old('favorite_site_id') == 'other' ? 'selected' : '' }}>
                                        🏢 Autre (créer mon propre site)
                                    </option>
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                                    <svg class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </div>
                            </div>

                            <!-- Message contextuel dynamique -->
                            <div class="mt-3 flex items-start gap-2 rounded-lg bg-blue-50/50 p-3 text-xs text-blue-700 transition-all"
                                 x-show="siteSelected === 'other'"
                                 x-transition:enter="transition ease-out duration-300"
                                 x-transition:enter-start="opacity-0 transform -translate-y-2"
                                 x-transition:enter-end="opacity-100 transform translate-y-0">
                                <svg class="mt-0.5 h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <p>Vous pourrez configurer votre propre site de parking lors de votre première connexion.</p>
                            </div>

                            <div class="mt-3 flex items-start gap-2 rounded-lg bg-emerald-50/50 p-3 text-xs text-emerald-700 transition-all"
                                 x-show="siteSelected && siteSelected !== 'other' && siteSelected !== ''"
                                 x-transition:enter="transition ease-out duration-300"
                                 x-transition:enter-start="opacity-0 transform -translate-y-2"
                                 x-transition:enter-end="opacity-100 transform translate-y-0">
                                <svg class="mt-0.5 h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <p>Excellent choix ! Vous pourrez réserver immédiatement sur ce site.</p>
                            </div>

                            <x-input-error :messages="$errors->get('favorite_site_id')" class="mt-2" />
                        </div>
                    </div>

                    <!-- Section 3: Sécurité -->
                    <div class="space-y-4 pt-3">
                        <h3 class="flex items-center gap-2 text-sm font-bold uppercase tracking-wider text-slate-900">
                            <svg class="h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                            Sécurité
                        </h3>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <!-- Mot de passe avec indicateur de force -->
                            <div class="relative">
                                <x-input-label for="password" :value="__('Mot de passe')" class="text-xs font-semibold uppercase tracking-wider text-slate-500" />
                                <div class="relative mt-2">
                                    <x-text-input 
                                        id="password" 
                                        x-bind:type="showPassword ? 'text' : 'password'"
                                        class="block w-full rounded-xl border-slate-200 bg-slate-50/50 pr-10 transition-all focus:border-emerald-500 focus:bg-white focus:ring-emerald-500/20" 
                                        name="password" 
                                        required 
                                        autocomplete="new-password"
                                        x-on:input="checkStrength($event.target.value)"
                                        placeholder="••••••••" />
                                    
                                    <!-- Toggle visibilité -->
                                    <button type="button" 
                                        x-on:click="showPassword = !showPassword"
                                        class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-600 focus:outline-none">
                                        <svg x-show="!showPassword" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        <svg x-show="showPassword" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="display: none;">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                        </svg>
                                    </button>
                                </div>

                                <!-- Indicateur de force du mot de passe -->
                                <div class="mt-2 flex gap-1" x-show="strength > 0">
                                    <template x-for="i in 5">
                                        <div class="h-1 flex-1 rounded-full transition-all duration-500"
                                             x-bind:class="{
                                                 'bg-red-400': i <= strength && strength <= 2,
                                                 'bg-yellow-400': i <= strength && strength === 3,
                                                 'bg-emerald-400': i <= strength && strength >= 4,
                                                 'bg-slate-200': i > strength
                                             }">
                                        </div>
                                    </template>
                                </div>
                                <p class="mt-1 text-[10px] text-slate-400" x-show="strength > 0">
                                    <span x-text="strength <= 2 ? 'Faible' : strength === 3 ? 'Moyen' : 'Fort'"></span>
                                </p>

                                <x-input-error :messages="$errors->get('password')" class="mt-2" />
                            </div>

                            <!-- Confirmation mot de passe -->
                            <div class="relative">
                                <x-input-label for="password_confirmation" :value="__('Confirmer le mot de passe')" class="text-xs font-semibold uppercase tracking-wider text-slate-500" />
                                <div class="relative mt-2">
                                    <x-text-input 
                                        id="password_confirmation" 
                                        x-bind:type="showPassword ? 'text' : 'password'"
                                        class="block w-full rounded-xl border-slate-200 bg-slate-50/50 transition-all focus:border-emerald-500 focus:bg-white focus:ring-emerald-500/20" 
                                        name="password_confirmation" 
                                        required 
                                        autocomplete="new-password"
                                        placeholder="••••••••" />
                                </div>
                                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                            </div>
                        </div>
                    </div>

                    <!-- Footer avec actions -->
                    <div class="flex flex-col-reverse mt-5 sm:flex-row sm:items-center sm:justify-between">
                        <a class="group flex items-center justify-center gap-2 text-sm font-semibold text-slate-500 transition-colors hover:text-emerald-600 sm:justify-start" href="{{ route('login') }}">
                            <svg class="h-4 w-4 transition-transform group-hover:-translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                            {{ __('Déjà inscrit ?') }}
                        </a>

                        <x-primary-button class="group relative w-full justify-center overflow-hidden rounded-xl bg-emerald-600 px-8 py-3 text-sm font-bold text-white shadow-lg shadow-emerald-600/30 transition-all hover:bg-emerald-700 hover:shadow-emerald-600/40 hover:-translate-y-0.5 active:translate-y-0 sm:w-auto">
                            <span class="relative z-10 flex items-center gap-2">
                                {{ __('Créer mon compte') }}
                                <svg class="h-4 w-4 transition-transform group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                </svg>
                            </span>
                            <div class="absolute inset-0 -translate-x-full bg-gradient-to-r from-transparent via-white/20 to-transparent transition-transform duration-700 group-hover:translate-x-full"></div>
                        </x-primary-button>
                    </div>

                    <!-- Trust badges -->
                    <div class="mt-6 flex flex-wrap items-center justify-center gap-3 border-t border-slate-100 pt-4 text-xs text-slate-400 sm:justify-start">
                        <div class="flex items-center gap-1.5">
                            <svg class="h-4 w-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                            <span>SSL Sécurisé</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <svg class="h-4 w-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                            <span>Données chiffrées</span>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Footer extérieur -->
        <p class="mt-5 text-center text-xs text-slate-400">
            En créant un compte, vous acceptez nos 
            <a href="#" class="text-emerald-600 hover:underline">Conditions d'utilisation</a> 
            et notre 
            <a href="#" class="text-emerald-600 hover:underline">Politique de confidentialité</a>.
        </p>
    </div>

    <!-- Styles pour animations personnalisées -->
    <style>
        @keyframes fade-in-up {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .animate-fade-in-up {
            animation: fade-in-up 0.6s ease-out;
        }
    </style>
</x-guest-layout>