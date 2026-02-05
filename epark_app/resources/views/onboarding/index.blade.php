<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Bienvenue sur ePark</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <!-- Header -->
            <div class="bg-gradient-to-r from-indigo-600 to-purple-600 rounded-2xl p-8 text-white shadow-lg mb-8">
                <div class="flex items-center gap-4">
                    <div class="w-16 h-16 bg-white/10 rounded-2xl flex items-center justify-center">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-2xl font-bold">Configuration rapide</h3>
                        <p class="text-indigo-100 mt-1">Choisissez votre site favori pour réserver plus rapidement</p>
                    </div>
                </div>
            </div>

            @if($errors->any())
                <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                    <ul class="list-disc list-inside">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div x-data="{ 
                action: 'choose',
                selectedSite: '',
                siteName: '',
                siteAddress: ''
            }">
                <form action="{{ route('onboarding.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="action" x-model="action">

                    <!-- Choix : Site existant ou Nouveau site -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
                        <button type="button" 
                            @click="action = 'choose'"
                            class="p-6 rounded-2xl border-2 text-left transition-all"
                            :class="action === 'choose' ? 'border-indigo-600 bg-indigo-50 ring-2 ring-indigo-600 ring-offset-2' : 'border-gray-200 bg-white hover:border-indigo-300'">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-xl flex items-center justify-center"
                                     :class="action === 'choose' ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-400'">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="font-bold text-gray-900">Choisir un site existant</h4>
                                    <p class="text-sm text-gray-500">Sélectionnez parmi les sites disponibles</p>
                                </div>
                            </div>
                        </button>

                        <button type="button" 
                            @click="action = 'create'"
                            class="p-6 rounded-2xl border-2 text-left transition-all"
                            :class="action === 'create' ? 'border-indigo-600 bg-indigo-50 ring-2 ring-indigo-600 ring-offset-2' : 'border-gray-200 bg-white hover:border-indigo-300'">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-xl flex items-center justify-center"
                                     :class="action === 'create' ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-400'">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="font-bold text-gray-900">Créer un nouveau site</h4>
                                    <p class="text-sm text-gray-500">Ajoutez votre propre emplacement</p>
                                </div>
                            </div>
                        </button>
                    </div>

                    <!-- Option A : Choisir un site existant -->
                    <div x-show="action === 'choose'" x-transition class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 mb-6">
                        <h3 class="font-bold text-gray-900 mb-4 flex items-center gap-2">
                            <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                            </svg>
                            Sites disponibles
                        </h3>

                        @if($sites->count() > 0)
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 max-h-96 overflow-y-auto">
                                @foreach($sites as $site)
                                    <label class="relative group cursor-pointer">
                                        <input type="radio" name="site_id" value="{{ $site->id }}" x-model="selectedSite" class="peer hidden">
                                        <div class="p-4 border-2 rounded-xl transition-all"
                                             :class="selectedSite == '{{ $site->id }}' ? 'border-indigo-600 bg-indigo-50' : 'border-gray-100 hover:border-indigo-200'">
                                            <div class="flex items-start gap-3">
                                                <div class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0"
                                                     :class="selectedSite == '{{ $site->id }}' ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-400'">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                                    </svg>
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <p class="font-bold text-gray-900 text-sm truncate">{{ $site->nom }}</p>
                                                    <p class="text-xs text-gray-500 truncate">{{ $site->adresse }}</p>
                                                    @if($site->user)
                                                        <p class="text-xs text-indigo-600 mt-1">Par {{ $site->user->name }}</p>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                        <div x-show="selectedSite == '{{ $site->id }}'" class="absolute -top-2 -right-2 bg-indigo-600 text-white rounded-full p-1 shadow-lg">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                                            </svg>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-8 text-gray-500">
                                <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                </svg>
                                <p>Aucun site disponible.</p>
                                <p class="text-sm mt-1">Créez le premier site !</p>
                            </div>
                        @endif
                    </div>

                    <!-- Option B : Créer un nouveau site -->
                    <div x-show="action === 'create'" x-transition class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 mb-6">
                        <h3 class="font-bold text-gray-900 mb-4 flex items-center gap-2">
                            <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            Nouveau site
                        </h3>

                        <div class="space-y-4">
                            <div>
                                <label for="site_nom" class="block text-sm font-medium text-gray-700 mb-1">
                                    Nom du site <span class="text-red-500">*</span>
                                </label>
                                <input 
                                    type="text" 
                                    id="site_nom" 
                                    name="site_nom" 
                                    x-model="siteName"
                                    placeholder="Ex: Parking Centre-Ville"
                                    class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            </div>

                            <div>
                                <label for="site_adresse" class="block text-sm font-medium text-gray-700 mb-1">
                                    Adresse <span class="text-red-500">*</span>
                                </label>
                                <input 
                                    type="text" 
                                    id="site_adresse" 
                                    name="site_adresse" 
                                    x-model="siteAddress"
                                    placeholder="Ex: 12 Rue de la Gare, 1000 Ville"
                                    class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                        </div>
                    </div>

                    <!-- Bouton de validation -->
                    <button 
                        type="submit"
                        class="w-full py-4 px-6 bg-indigo-600 text-white font-bold rounded-xl hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-200 flex items-center justify-center gap-2"
                        :disabled="(action === 'choose' && !selectedSite) || (action === 'create' && (!siteName || !siteAddress))">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Continuer
                    </button>
                </form>
            </div>

        </div>
    </div>
</x-app-layout>
