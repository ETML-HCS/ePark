<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="{{ route('places.mes') }}" class="p-2 bg-white rounded-xl hover:bg-gray-50 transition-all shadow-sm border border-gray-200 hover:border-gray-300 group">
                    <svg class="w-5 h-5 text-gray-600 group-hover:text-gray-900 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </a>
                <h2 class="font-black text-2xl text-gray-900 flex items-center gap-3">
                    <div class="p-2 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl shadow-lg shadow-indigo-200">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                    </div>
                    Ajouter une place
                </h2>
            </div>
        </div>
    </x-slot>

    <style>
        @keyframes slide-up {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-slide-up {
            animation: slide-up 0.5s ease-out forwards;
        }
        .delay-100 { animation-delay: 100ms; }
        .delay-200 { animation-delay: 200ms; }
        .delay-300 { animation-delay: 300ms; }
        
        .form-group:focus-within label {
            color: #4f46e5;
        }
        .form-group:focus-within svg {
            color: #4f46e5;
        }
        
        /* Toggle switch animation */
        .toggle-checkbox:checked + .toggle-label {
            background-color: #4f46e5;
        }
        .toggle-checkbox:checked + .toggle-label:before {
            transform: translateX(100%);
        }
        
        @keyframes pulse-soft {
            0%, 100% { box-shadow: 0 0 0 0 rgba(79, 70, 229, 0.4); }
            50% { box-shadow: 0 0 0 8px rgba(79, 70, 229, 0.1); }
        }
        .animate-pulse-soft {
            animation: pulse-soft 2s infinite;
        }
    </style>

    <div class="py-8 bg-gray-50 min-h-screen">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            
            @if($errors->any())
                <div class="mb-6 bg-red-50 border-l-4 border-red-500 rounded-xl p-4 shadow-sm animate-slide-up">
                    <div class="flex items-start gap-3">
                        <svg class="w-6 h-6 text-red-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <div class="flex-1">
                            <h3 class="font-bold text-red-900 mb-2">Erreurs de validation</h3>
                            <ul class="space-y-1">
                                @foreach($errors->all() as $error)
                                    <li class="text-sm text-red-700 flex items-center gap-2">
                                        <span class="w-1.5 h-1.5 bg-red-500 rounded-full"></span>
                                        {{ $error }}
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif

            <div class="bg-white rounded-3xl shadow-xl shadow-gray-200/50 border border-gray-200 overflow-hidden animate-slide-up delay-100">
                
                <!-- Form Header -->
                <div class="bg-gradient-to-br from-indigo-600 via-purple-600 to-pink-500 px-8 py-10 text-white relative overflow-hidden">
                    <!-- Decorative elements -->
                    <div class="absolute top-0 right-0 -mt-10 -mr-10 w-40 h-40 bg-white/10 rounded-full blur-3xl"></div>
                    <div class="absolute bottom-0 left-0 -mb-10 -ml-10 w-32 h-32 bg-purple-500/20 rounded-full blur-2xl"></div>
                    
                    <div class="flex items-center gap-4 relative z-10">
                        <div class="w-16 h-16 bg-white/20 backdrop-blur-sm rounded-2xl flex items-center justify-center shadow-inner border border-white/30">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-3xl font-black mb-2">Nouvelle place de parking</h3>
                            <p class="text-indigo-100 text-base">Remplissez le formulaire et créez votre place en quelques clics</p>
                        </div>
                    </div>
                </div>

                <!-- Form Content -->
                <form method="POST" action="{{ route('places.store') }}" class="px-8 py-10 space-y-8" x-data="{ 
                    newSite: {{ $sites->isEmpty() ? 'true' : 'false' }},
                    price: '{{ old('hourly_price', '2.00') }}',
                    cancelDeadline: '{{ old('cancel_deadline_hours', '12') }}',
                    characteristics: '{{ old('caracteristiques', '') }}',
                    showPreview: false,
                    isGroupReserved: {{ old('is_group_reserved', '0') == '1' ? 'true' : 'false' }},
                    groupSource: '{{ old('group_source', !empty($savedGroupEntries ?? []) ? 'existing' : 'manual') }}'
                }">
                    @csrf

                    <!-- Hidden field to track new site creation -->
                    <input type="hidden" name="create_new_site" :value="newSite ? '1' : '0'">

                    <!-- Affectation groupe -->
                    <div class="form-group space-y-4 animate-slide-up delay-200">
                        <label class="text-base font-bold text-gray-900 flex items-center gap-2">
                            <svg class="w-4 h-4 text-indigo-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11V7a5 5 0 0110 0v4m-1 8H8a2 2 0 01-2-2v-4a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2z"></path>
                            </svg>
                            Groupe secret
                        </label>

                        <input type="hidden" name="is_group_reserved" value="0">
                        <label class="inline-flex items-center gap-2">
                            <input type="checkbox" name="is_group_reserved" value="1" x-model="isGroupReserved" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            <span class="text-sm font-semibold text-gray-800">Cette place appartient à un groupe secret</span>
                        </label>

                        <div x-show="isGroupReserved" x-transition class="space-y-3 p-4 rounded-xl border-2 border-indigo-100 bg-indigo-50/40">
                            <div class="flex gap-2">
                                <button type="button" @click="groupSource='existing'" :class="groupSource==='existing' ? 'bg-indigo-600 text-white' : 'bg-white text-gray-600 border border-gray-200'" class="px-3 py-2 rounded-lg text-xs font-bold transition-all">Depuis mes groupes</button>
                                <button type="button" @click="groupSource='manual'" :class="groupSource==='manual' ? 'bg-indigo-600 text-white' : 'bg-white text-gray-600 border border-gray-200'" class="px-3 py-2 rounded-lg text-xs font-bold transition-all">Nouveau groupe</button>
                            </div>

                            <input type="hidden" name="group_source" :value="groupSource">

                            <div x-show="groupSource==='existing'" class="space-y-2">
                                <label for="secret_group_index" class="text-xs font-bold text-gray-600 block">Sélectionner un groupe enregistré</label>
                                <select id="secret_group_index" name="secret_group_index" class="w-full px-4 py-3 bg-white border-2 border-gray-200 rounded-xl focus:ring-0 focus:border-indigo-500 transition-all font-medium text-gray-900">
                                    <option value="">-- Choisir --</option>
                                    @foreach(($savedGroupEntries ?? []) as $idx => $entry)
                                        <option value="{{ $idx }}" {{ (string) old('secret_group_index', '') === (string) $idx ? 'selected' : '' }}>{{ $entry['name'] !== '' ? $entry['name'] : 'Groupe sans nom' }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div x-show="groupSource==='manual'" class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <div>
                                    <label for="group_name" class="text-xs font-bold text-gray-600 mb-1 block">Nom du groupe</label>
                                    <input id="group_name" type="text" name="group_name" value="{{ old('group_name') }}" placeholder="Ex: ETML/CFPV" class="w-full px-4 py-3 bg-white border-2 border-gray-200 rounded-xl focus:ring-0 focus:border-indigo-500 transition-all font-medium text-gray-900">
                                </div>
                                <div>
                                    <label for="group_access_code" class="text-xs font-bold text-gray-600 mb-1 block">Code du groupe</label>
                                    <input id="group_access_code" type="text" name="group_access_code" value="{{ old('group_access_code') }}" placeholder="Ex: etml3865" class="w-full px-4 py-3 bg-white border-2 border-gray-200 rounded-xl focus:ring-0 focus:border-indigo-500 transition-all font-medium text-gray-900">
                                </div>
                            </div>

                            <div>
                                <label for="group_allowed_email_domains_raw" class="text-xs font-bold text-gray-600 mb-1 block">Domaines email autorisés (optionnel)</label>
                                <textarea id="group_allowed_email_domains_raw" name="group_allowed_email_domains_raw" rows="2" placeholder="Ex: eduvaud.ch" class="w-full px-4 py-3 bg-white border-2 border-gray-200 rounded-xl focus:ring-0 focus:border-indigo-500 transition-all font-medium text-gray-900">{{ old('group_allowed_email_domains_raw') }}</textarea>
                                <p class="mt-1 text-[11px] text-gray-500">Un domaine par ligne. Les utilisateurs connectés avec ces emails auront accès au groupe.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Site Selection -->
                    <div class="form-group space-y-4 animate-slide-up delay-200">
                        <div class="flex items-center justify-between gap-4">
                            <label class="text-base font-bold text-gray-900 flex items-center gap-2">
                                <svg class="w-4 h-4 text-indigo-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                </svg>
                                Site
                                <span class="text-red-500">*</span>
                            </label>
                            
                            <!-- Toggle Switch amélioré -->
                            <div class="flex items-center gap-1 bg-gray-100 rounded-full p-1 shrink-0">
                                <button 
                                    type="button"
                                    @click="newSite = false"
                                    :class="!newSite ? 'bg-white text-indigo-600 shadow-sm' : 'text-gray-500 hover:text-gray-700'"
                                    class="px-3 py-2 rounded-full text-xs font-bold transition-all duration-200 flex items-center gap-1 whitespace-nowrap">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                                    </svg>
                                    Existant
                                </button>
                                <button 
                                    type="button"
                                    @click="newSite = true"
                                    :class="newSite ? 'bg-white text-indigo-600 shadow-sm' : 'text-gray-500 hover:text-gray-700'"
                                    class="px-3 py-2 rounded-full text-xs font-bold transition-all duration-200 flex items-center gap-1 whitespace-nowrap">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                    </svg>
                                    Nouveau
                                </button>
                            </div>
                        </div>

                        <!-- Site existant -->
                        <div x-show="!newSite" 
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 transform -translate-y-2"
                             x-transition:enter-end="opacity-100 transform translate-y-0">
                            <select 
                                id="site_id" 
                                name="site_id" 
                                class="w-full px-4 py-3 bg-gray-50 border-2 border-gray-200 rounded-xl focus:ring-0 focus:border-indigo-500 transition-all font-medium text-base text-gray-900 hover:border-gray-300"
                                :required="!newSite">
                                <option value="">-- Sélectionner un site --</option>
                                @foreach($sites as $site)
                                    <option value="{{ $site->id }}" {{ old('site_id', $selectedSiteId ?? '') == $site->id ? 'selected' : '' }}>
                                        {{ $site->nom }} - {{ $site->adresse }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Nouveau site -->
                        <div x-show="newSite" 
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 transform -translate-y-2"
                             x-transition:enter-end="opacity-100 transform translate-y-0"
                             class="space-y-4 bg-indigo-50/50 p-5 rounded-xl border-2 border-indigo-200">
                            <div>
                                <label for="site_nom" class="text-sm font-bold text-gray-900 mb-2 block">Nom du site</label>
                                <input
                                    type="text"
                                    id="site_nom"
                                    name="site_nom"
                                    value="{{ old('site_nom') }}"
                                    placeholder="Ex: Parking Centre-Ville"
                                    class="w-full px-4 py-3 bg-white border-2 border-gray-200 rounded-xl focus:ring-0 focus:border-indigo-500 transition-all font-medium text-base text-gray-900"
                                    :required="newSite"
                                    :disabled="!newSite">
                            </div>
                            <div>
                                <label for="site_adresse" class="text-sm font-bold text-gray-900 mb-2 block">Adresse</label>
                                <input
                                    type="text"
                                    id="site_adresse"
                                    name="site_adresse"
                                    value="{{ old('site_adresse') }}"
                                    placeholder="Ex: Rue du Parking 12, 1000 Lausanne"
                                    class="w-full px-4 py-3 bg-white border-2 border-gray-200 rounded-xl focus:ring-0 focus:border-indigo-500 transition-all font-medium text-base text-gray-900"
                                    :required="newSite"
                                    :disabled="!newSite">
                            </div>
                        </div>
                    </div>

                    <!-- Nom Field -->
                    <div class="form-group animate-slide-up delay-200">
                        <label for="nom" class="text-base font-bold text-gray-900 mb-3 flex items-center gap-2">
                            <svg class="w-4 h-4 text-indigo-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                            </svg>
                            Nom de la place
                            <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <input 
                                type="text" 
                                id="nom" 
                                name="nom" 
                                value="{{ old('nom') }}"
                                placeholder="Ex: Place A12, Emplacement 5..."
                                class="w-full px-4 py-3 bg-gray-50 border-2 border-gray-200 rounded-xl focus:ring-0 focus:border-indigo-500 transition-all font-medium text-base text-gray-900 hover:border-gray-300"
                                required>
                            <div class="absolute right-3 top-3 text-gray-400">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                                </svg>
                            </div>
                        </div>
                        <p class="mt-2 text-sm text-gray-500">Identifiant unique visible par les clients</p>
                    </div>

                    <!-- Caractéristiques Field -->
                    <div class="form-group animate-slide-up delay-300">
                        <label for="caracteristiques" class="text-base font-bold text-gray-900 mb-3 flex items-center gap-2">
                            <svg class="w-4 h-4 text-indigo-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            Caractéristiques
                            <span class="text-sm font-normal text-gray-500 ml-1">(optionnel)</span>
                        </label>
                        <textarea 
                            id="caracteristiques" 
                            name="caracteristiques" 
                            rows="3"
                            x-model="characteristics"
                            placeholder="Ex: Proche métro, Éclairage LED, Surveillance vidéo..."
                            class="w-full px-4 py-3 bg-gray-50 border-2 border-gray-200 rounded-xl focus:ring-0 focus:border-indigo-500 transition-all font-medium text-base text-gray-900 resize-none hover:border-gray-300"></textarea>
                        
                        <!-- Raccourcis avec descriptions claires -->
<div class="mt-4 flex justify-center">
    <div class="flex flex-wrap justify-center gap-2 max-w-lg">
        <button type="button" @click="characteristics = (characteristics ? characteristics + ', ' : '') + 'Accès PMR'" class="inline-flex items-center gap-1.5 px-3 py-2 bg-blue-50 border border-blue-200 rounded-full hover:bg-blue-100 hover:border-blue-300 transition-all">
            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path>
            </svg>
            <span class="text-xs font-semibold text-blue-800">Accès PMR</span>
        </button>

        <button type="button" @click="characteristics = (characteristics ? characteristics + ', ' : '') + 'Place couverte'" class="inline-flex items-center gap-1.5 px-3 py-2 bg-amber-50 border border-amber-200 rounded-full hover:bg-amber-100 hover:border-amber-300 transition-all">
            <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-3m0 0l7-4 7 4M5 9v10a1 1 0 001 1h12a1 1 0 001-1V9m-9 3l3-3"></path>
            </svg>
            <span class="text-xs font-semibold text-amber-800">Couverte</span>
        </button>

        <button type="button" @click="characteristics = (characteristics ? characteristics + ', ' : '') + 'Sécurisée (vidéo)'" class="inline-flex items-center gap-1.5 px-3 py-2 bg-green-50 border border-green-200 rounded-full hover:bg-green-100 hover:border-green-300 transition-all">
            <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
            </svg>
            <span class="text-xs font-semibold text-green-800">Sécurisée</span>
        </button>

        <button type="button" @click="characteristics = (characteristics ? characteristics + ', ' : '') + 'Accès par Badge'" class="inline-flex items-center gap-1.5 px-3 py-2 bg-purple-50 border border-purple-200 rounded-full hover:bg-purple-100 hover:border-purple-300 transition-all">
            <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
            </svg>
            <span class="text-xs font-semibold text-purple-800">Badge</span>
        </button>
    </div>
</div>
                        
                        <p class="mt-3 text-xs text-gray-500 italic">💡 Cliquez sur les raccourcis ou tapez directement vos caractéristiques personnalisées</p>
                    </div>

                    <!-- Tarif horaire -->
                    <div class="form-group animate-slide-up delay-300">
                        <label for="hourly_price" class="text-base font-bold text-gray-900 mb-4 flex items-center gap-2">
                            <svg class="w-4 h-4 text-indigo-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Tarif horaire
                            <span class="text-red-500">*</span>
                        </label>
                        
                        <!-- Disposition 2 colonnes: input à gauche + raccourcis à droite -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            
                            <!-- COLONNE 1: Input principal -->
                            <div>
                                <div class="relative">
                                    <span class="absolute right-4 top-4 text-gray-500 font-bold text-lg">CHF</span>
                                    <input 
                                        type="number" 
                                        id="hourly_price" 
                                        name="hourly_price" 
                                        x-model="price"
                                        min="0" 
                                        step="0.50"
                                        class="w-full pl-4 pr-14 py-4 bg-gray-50 border-2 border-gray-200 rounded-xl focus:ring-0 focus:border-indigo-500 transition-all font-bold text-3xl text-gray-900 hover:border-gray-300"
                                        required>
                                </div>
                                <p class="mt-3 text-sm text-gray-500">Saisie manuelle du prix par heure</p>
                            </div>
                            
                            <!-- COLONNE 2: Raccourcis -->
                            <div class="space-y-2">
                                {{-- <!-- Titre raccourcis -->
                                <p class="text-xs font-semibold text-gray-600 uppercase tracking-wider mb-3">Raccourcis rapides:</p>
                                 --}}
                                <!-- Ligne 1: Tous les prix en une ligne -->
                                <div class="flex gap-1.5 flex-wrap">
                                    <button type="button" @click="price = '1.00'" :class="price === '1.00' ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-indigo-100'" class="flex-1 min-w-fit px-2.5 py-2.5 rounded-lg text-xs font-bold transition-all border border-transparent hover:border-indigo-300">1.00</button>
                                    <button type="button" @click="price = '2.00'" :class="price === '2.00' ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-indigo-100'" class="flex-1 min-w-fit px-2.5 py-2.5 rounded-lg text-xs font-bold transition-all border border-transparent hover:border-indigo-300">2.00</button>
                                    <button type="button" @click="price = '2.50'" :class="price === '2.50' ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-indigo-100'" class="flex-1 min-w-fit px-2.5 py-2.5 rounded-lg text-xs font-bold transition-all border border-transparent hover:border-indigo-300">2.50</button>
                                    <button type="button" @click="price = '3.00'" :class="price === '3.00' ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-indigo-100'" class="flex-1 min-w-fit px-2.5 py-2.5 rounded-lg text-xs font-bold transition-all border border-transparent hover:border-indigo-300">3.00</button>
                                    <button type="button" @click="price = '3.50'" :class="price === '3.50' ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-indigo-100'" class="flex-1 min-w-fit px-2.5 py-2.5 rounded-lg text-xs font-bold transition-all border border-transparent hover:border-indigo-300">3.50</button>
                                    
                                </div>
                                
                                <!-- Ligne 2: Gratuit fullwidth -->
                                <button type="button" @click="price = '0.00'" :class="price === '0.00' ? 'bg-green-600 text-white shadow-lg shadow-green-200' : 'bg-gray-100 text-gray-600 hover:bg-green-100 hover:text-green-600'" class="w-full px-4 py-3 rounded-lg font-bold transition-all border border-transparent hover:border-green-300">🎁 Gratuit</button>
                            </div>
                        </div>
                        
                        <p class="mt-4 text-sm text-gray-500">💡 Prix par heure facturé aux locataires</p>
                    </div>

                    <!-- Délai d'annulation -->
                    <div class="form-group animate-slide-up delay-300">
                        <label for="cancel_deadline_hours" class="text-base font-bold text-gray-900 mb-3 flex items-center gap-2">
                            <svg class="w-4 h-4 text-indigo-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Délai d'annulation
                            <span class="text-red-500">*</span>
                        </label>
                        <input type="hidden" name="cancel_deadline_hours" x-model="cancelDeadline">
                        <div class="grid grid-cols-2 gap-3">
                            <button type="button" @click="cancelDeadline = '12'" :class="cancelDeadline === '12' ? 'border-indigo-600 bg-indigo-50' : 'border-gray-200 bg-white'" class="relative flex items-center p-4 border-2 rounded-xl cursor-pointer hover:border-indigo-300 transition-all gap-3">
                                <div class="flex-1 text-left">
                                    <div class="font-bold text-gray-900 text-base" :class="cancelDeadline === '12' ? 'text-indigo-900' : 'text-gray-900'">12 heures</div>
                                    <div class="text-sm" :class="cancelDeadline === '12' ? 'text-indigo-700' : 'text-gray-500'">Avant le créneau</div>
                                </div>
                                <div class="w-5 h-5 rounded-full border-2 flex items-center justify-center flex-shrink-0" :class="cancelDeadline === '12' ? 'border-indigo-600 bg-indigo-600' : 'border-gray-300'">
                                    <svg class="w-3 h-3 text-white" :class="cancelDeadline === '12' ? 'opacity-100' : 'opacity-0'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </div>
                            </button>
                            <button type="button" @click="cancelDeadline = '24'" :class="cancelDeadline === '24' ? 'border-indigo-600 bg-indigo-50' : 'border-gray-200 bg-white'" class="relative flex items-center p-4 border-2 rounded-xl cursor-pointer hover:border-indigo-300 transition-all gap-3">
                                <div class="flex-1 text-left">
                                    <div class="font-bold text-gray-900 text-base" :class="cancelDeadline === '24' ? 'text-indigo-900' : 'text-gray-900'">24 heures</div>
                                    <div class="text-sm" :class="cancelDeadline === '24' ? 'text-indigo-700' : 'text-gray-500'">Avant le créneau</div>
                                </div>
                                <div class="w-5 h-5 rounded-full border-2 flex items-center justify-center flex-shrink-0" :class="cancelDeadline === '24' ? 'border-indigo-600 bg-indigo-600' : 'border-gray-300'">
                                    <svg class="w-3 h-3 text-white" :class="cancelDeadline === '24' ? 'opacity-100' : 'opacity-0'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </div>
                            </button>
                        </div>
                    </div>

                    <!-- Info Box -->
                    <div class="p-5 bg-gradient-to-r from-blue-50 to-indigo-50 border-2 border-blue-200 rounded-xl animate-slide-up delay-300">
                        <div class="flex items-start gap-3">
                            <div class="p-2 bg-blue-100 rounded-lg">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <h4 class="font-bold text-gray-900 text-base mb-1">Prochaine étape</h4>
                                <p class="text-base text-gray-600">Après création, configurez les disponibilités de cette place pour qu'elle apparaisse dans les recherches.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center gap-3 pt-8 border-t border-gray-200 animate-slide-up delay-300">
                        <a href="{{ route('places.mes') }}" class="flex-1 text-center py-4 bg-white border-2 border-gray-200 text-gray-700 rounded-xl font-bold hover:bg-gray-50 hover:border-gray-300 transition-all text-base">
                            Annuler
                        </a>
                        <button type="submit" class="flex-1 flex items-center justify-center gap-2 py-4 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl font-bold hover:from-indigo-700 hover:to-purple-700 transition-all shadow-lg shadow-indigo-200 hover:shadow-xl hover:shadow-indigo-300 transform hover:-translate-y-0.5 active:translate-y-0 text-base">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Créer la place
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>