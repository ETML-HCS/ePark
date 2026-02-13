<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-4 animate-fade-in-down">
            <div class="p-3 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-2xl shadow-xl shadow-indigo-500/30 ring-4 ring-indigo-100">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
            </div>
            <div>
                <h2 class="font-black text-2xl sm:text-3xl text-gray-900 tracking-tight">Mon Profil</h2>
                <p class="text-sm text-gray-500 font-medium">Gérez vos informations personnelles et sécurité</p>
            </div>
        </div>
    </x-slot>

    <div class="py-8 bg-gradient-to-b from-gray-50 via-white to-gray-50 min-h-screen">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6" x-data="{ activeTab: 'info', showDeleteConfirm: false }">

            <!-- HERO CARD - VISUALISATION MAXIMALE -->
            <div class="relative overflow-hidden bg-gradient-to-br from-indigo-600 via-purple-600 to-indigo-800 rounded-3xl p-8 sm:p-10 text-white shadow-2xl shadow-indigo-500/30 group">
                <!-- Overlay gradient pour meilleur contraste -->
                <div class="absolute inset-0 bg-gradient-to-br from-indigo-900/20 via-transparent to-purple-900/30"></div>
                
                <!-- Pattern hexagonal plus visible -->
                <div class="absolute inset-0 opacity-40">
                    <svg class="w-full h-full" viewBox="0 0 100 100" preserveAspectRatio="none">
                        <defs>
                            <pattern id="hexagons" width="20" height="34.6" patternUnits="userSpaceOnUse" patternTransform="scale(0.6)">
                                <path d="M10 0L20 5.77v11.54L10 23.08 0 17.31V5.77L10 0z" fill="none" stroke="white" stroke-width="1.5" opacity="0.8"/>
                                <path d="M10 0v5.77M0 17.31l10 5.77 10-5.77" stroke="white" stroke-width="0.5" opacity="0.4"/>
                            </pattern>
                        </defs>
                        <rect width="100%" height="100%" fill="url(#hexagons)"/>
                    </svg>
                </div>

                <!-- Cercles décoratifs animés -->
                <div class="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full blur-3xl -translate-y-1/2 translate-x-1/2 animate-pulse"></div>
                <div class="absolute bottom-0 left-0 w-48 h-48 bg-purple-500/20 rounded-full blur-2xl translate-y-1/2 -translate-x-1/2"></div>

                <div class="relative flex flex-col sm:flex-row items-center sm:items-start gap-6 sm:gap-8">
                    <!-- Avatar XL avec effet néon -->
                    <div class="relative">
                        <div class="absolute -inset-2 bg-gradient-to-r from-white/30 to-purple-400/30 rounded-3xl blur-xl opacity-75 group-hover:opacity-100 transition-opacity duration-500"></div>
                        <div class="relative w-24 h-24 sm:w-28 sm:h-28 bg-gradient-to-br from-white/20 to-white/5 backdrop-blur-xl rounded-3xl flex items-center justify-center border-2 border-white/40 shadow-2xl transform group-hover:scale-105 transition-all duration-500 ring-4 ring-white/10">
                            <span class="text-5xl sm:text-6xl font-black text-white drop-shadow-[0_2px_10px_rgba(0,0,0,0.3)]">
                                {{ substr(auth()->user()->name, 0, 1) }}
                            </span>
                        </div>
                        <!-- Indicateur statut -->
                        <div class="absolute -bottom-2 -right-2 w-8 h-8 bg-emerald-400 border-4 border-indigo-800 rounded-full flex items-center justify-center shadow-lg shadow-emerald-500/50" title="En ligne">
                            <div class="w-3 h-3 bg-white rounded-full animate-pulse"></div>
                        </div>
                    </div>

                    <div class="flex-1 text-center sm:text-left z-10 space-y-3">
                        <div class="flex flex-col sm:flex-row sm:items-center gap-3 justify-center sm:justify-start">
                            <h3 class="text-2xl sm:text-3xl font-black tracking-tight text-white drop-shadow-lg">
                                {{ auth()->user()->name }}
                            </h3>
                        </div>

                        <div class="flex flex-wrap items-center justify-center sm:justify-start gap-2 pt-3">
                            @if (auth()->user()->email_verified_at)
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-emerald-800/80 rounded-full text-[11px] font-black text-emerald-50 shadow-lg shadow-emerald-900/40">
                                    <svg class="w-4 h-4" fill="gold" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                    VÉRIFIÉ
                                </span>
                            @endif

                            <span class="inline-flex items-center gap-2 text-white text-[11px] font-semibold bg-slate-900/80 rounded-full px-3 py-1.5 shadow-md shadow-slate-900/40">
                                <svg class="w-4 h-4 opacity-80" fill="gold" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                </svg>
                                {{ auth()->user()->email }}
                            </span>
                            @if (auth()->user()->role)
                                <span class="inline-flex items-center gap-2 px-3 py-1.5 bg-amber-800/80 rounded-xl text-[11px] font-bold text-amber-50 shadow-lg shadow-amber-900/40">
                                    <svg class="w-4 h-4 text-amber-300" fill="gold" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"></path>
                                    </svg>
                                    {{ ucfirst(auth()->user()->role) }}
                                </span>
                            @endif

                            <span class="inline-flex items-center gap-2 px-3 py-1.5 bg-teal-900/80 rounded-xl text-[11px] font-bold text-teal-50 shadow-lg shadow-teal-900/45">
                                <svg class="w-4 h-4" fill="gold" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                                Membre depuis {{ auth()->user()->created_at->format('M Y') }}
                            </span>
                        </div>
                    </div>

                    <!-- Bouton édition flottant -->
                    <button onclick="document.getElementById('info-section').scrollIntoView({behavior: 'smooth'})" 
                        class="absolute top-3 right-3 p-2.5 bg-white/20 hover:bg-white/30 rounded-xl backdrop-blur-md transition-all hover:scale-110 shadow-lg border border-white/30 group/btn">
                        <svg class="w-6 h-6 text-white group-hover/btn:rotate-12 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Navigation par onglets -->
            <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-2 flex flex-wrap gap-2 sticky top-4 z-30 backdrop-blur-xl bg-white/95">
                <button @click="activeTab = 'info'" :class="{ 'bg-indigo-100 text-indigo-700 shadow-md ring-2 ring-indigo-500/30': activeTab === 'info', 'text-gray-600 hover:bg-gray-50 hover:text-gray-900': activeTab !== 'info' }" class="flex-1 flex items-center justify-center gap-2 px-4 py-3 rounded-xl text-sm font-bold transition-all duration-200">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span class="hidden sm:inline">Informations</span>
                </button>
                <button @click="activeTab = 'groups'" :class="{ 'bg-emerald-100 text-emerald-700 shadow-md ring-2 ring-emerald-500/30': activeTab === 'groups', 'text-gray-600 hover:bg-gray-50 hover:text-gray-900': activeTab !== 'groups' }" class="flex-1 flex items-center justify-center gap-2 px-4 py-3 rounded-xl text-sm font-bold transition-all duration-200">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    <span class="hidden sm:inline">Groupes</span>
                </button>
                <button @click="activeTab = 'security'" :class="{ 'bg-purple-100 text-purple-700 shadow-md ring-2 ring-purple-500/30': activeTab === 'security', 'text-gray-600 hover:bg-gray-50 hover:text-gray-900': activeTab !== 'security' }" class="flex-1 flex items-center justify-center gap-2 px-4 py-3 rounded-xl text-sm font-bold transition-all duration-200">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                    <span class="hidden sm:inline">Sécurité</span>
                </button>
            </div>

            <!-- Section Informations -->
            <div id="info-section" x-show="activeTab === 'info'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform translate-y-4" x-transition:enter-end="opacity-100 transform translate-y-0" class="bg-white shadow-xl shadow-gray-200/50 border border-gray-100 rounded-3xl overflow-hidden hover:shadow-2xl transition-all duration-300">
                <div class="bg-gradient-to-r from-indigo-50 via-purple-50 to-indigo-50 p-6 border-b border-gray-100">
                    <div class="flex items-center gap-3">
                        <div class="p-3 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl shadow-lg shadow-indigo-500/30">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-black text-gray-900">Informations du profil</h3>
                            <p class="text-sm text-gray-500">Mettez à jour vos informations personnelles</p>
                        </div>
                    </div>
                </div>
                <div class="p-6 sm:p-8">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <!-- Section Groupes -->
            <div x-show="activeTab === 'groups'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform translate-y-4" x-transition:enter-end="opacity-100 transform translate-y-0" class="bg-white shadow-xl shadow-gray-200/50 border border-gray-100 rounded-3xl overflow-hidden hover:shadow-2xl transition-all duration-300">
                <div class="bg-gradient-to-r from-emerald-50 via-teal-50 to-emerald-50 p-6 border-b border-gray-100">
                    <div class="flex items-center gap-3">
                        <div class="p-3 bg-gradient-to-br from-emerald-500 to-teal-600 rounded-xl shadow-lg shadow-emerald-500/30">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-black text-gray-900">Groupes secrets</h3>
                            <p class="text-sm text-gray-500">Rejoignez des groupes privés via leur nom et code</p>
                        </div>
                    </div>
                </div>
                <div class="p-6 sm:p-8">
                    @include('profile.partials.secret-groups-form')
                </div>
            </div>

            <!-- Section Sécurité -->
            <div x-show="activeTab === 'security'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform translate-y-4" x-transition:enter-end="opacity-100 transform translate-y-0" class="space-y-6">
                
                <!-- Modifier mot de passe -->
                <div class="bg-white shadow-xl shadow-gray-200/50 border border-gray-100 rounded-3xl overflow-hidden hover:shadow-2xl transition-all duration-300">
                    <div class="bg-gradient-to-r from-purple-50 via-pink-50 to-purple-50 p-6 border-b border-gray-100">
                        <div class="flex items-center gap-3">
                            <div class="p-3 bg-gradient-to-br from-purple-500 to-pink-600 rounded-xl shadow-lg shadow-purple-500/30">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-black text-gray-900">Modifier le mot de passe</h3>
                                <p class="text-sm text-gray-500">Assurez-vous d'utiliser un mot de passe sécurisé</p>
                            </div>
                        </div>
                    </div>
                    <div class="p-6 sm:p-8">
                        @include('profile.partials.update-password-form')
                    </div>
                </div>
            </div>

            <!-- SUPPRESSION COMPACTE - TOUJOURS VISIBLE -->
            <div class="bg-gradient-to-r from-red-50 via-orange-50 to-red-50 border-2 border-red-200 rounded-2xl p-4 hover:shadow-lg hover:shadow-red-500/10 transition-all duration-300" :class="{ 'ring-2 ring-red-400 shadow-xl': showDeleteConfirm }">
                <div class="flex items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-gradient-to-br from-red-500 to-orange-600 rounded-xl shadow-lg shadow-red-500/30 shrink-0">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-sm font-black text-red-900 uppercase tracking-wide">Zone de danger</h4>
                            <p class="text-xs text-red-600 font-medium">Suppression définitive du compte</p>
                        </div>
                    </div>
                    
                    <button @click="showDeleteConfirm = !showDeleteConfirm" 
                        :class="showDeleteConfirm ? 'bg-red-600 text-white' : 'bg-white text-red-600 hover:bg-red-50'"
                        class="px-4 py-2 rounded-lg text-sm font-bold border-2 border-red-200 transition-all duration-200 flex items-center gap-2 shrink-0">
                        <span x-text="showDeleteConfirm ? 'Annuler' : 'Supprimer'"></span>
                        <svg x-show="!showDeleteConfirm" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                        <svg x-show="showDeleteConfirm" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <!-- Formulaire de suppression (expandable) -->
                <div x-show="showDeleteConfirm" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 transform -translate-y-2" x-transition:enter-end="opacity-100 transform translate-y-0" class="mt-4 pt-4 border-t border-red-200">
                    <div class="bg-white rounded-xl p-4 border border-red-100">
                        <p class="text-sm text-red-700 mb-4 font-medium flex items-center gap-2">
                            <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                            </svg>
                            Cette action est irréversible. Toutes vos données seront perdues.
                        </p>
                        @include('profile.partials.delete-user-form')
                    </div>
                </div>
            </div>

        </div>
    </div>

    <style>
        @keyframes fade-in-down {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in-down {
            animation: fade-in-down 0.6s ease-out;
        }
    </style>
</x-app-layout>