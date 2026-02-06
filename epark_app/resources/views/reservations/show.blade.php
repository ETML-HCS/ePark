<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="{{ route('reservations.index') }}" class="p-2 bg-white rounded-xl hover:bg-gray-50 transition-all shadow-sm">
                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </a>
                <h2 class="font-black text-2xl text-gray-900 flex items-center gap-3">
                    <div class="p-2 bg-indigo-100 rounded-xl">
                        <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    Réservation #{{ $reservation->id }}
                </h2>
            </div>
        </div>
    </x-slot>

    <div class="py-8 bg-gradient-to-b from-gray-50 to-white min-h-screen">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <!-- Status Hero Card -->
            <div class="mb-8 bg-gradient-to-br 
                @if($reservation->statut === 'confirmée') from-green-400 to-green-600
                @elseif($reservation->statut === 'en_attente') from-yellow-400 to-yellow-600
                @elseif($reservation->statut === 'annulée') from-red-400 to-red-600
                @else from-gray-400 to-gray-600
                @endif
                rounded-3xl shadow-2xl p-8 text-white overflow-hidden relative">
                <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHZpZXdCb3g9IjAgMCA2MCA2MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48ZyBmaWxsPSJub25lIiBmaWxsLXJ1bGU9ImV2ZW5vZGQiPjxnIGZpbGw9IiNmZmZmZmYiIGZpbGwtb3BhY2l0eT0iMC4wNSI+PHBhdGggZD0iTTM2IDM0di00aC0ydjRoLTR2Mmg0djRoMnYtNGg0di0yaC00em0wLTMwVjBoLTJ2NGgtNHYyaDR2NGgyVjZoNFY0aC00ek02IDM0di00SDR2NGg2djJINFY0aDJ2NGg0VjZINlY0aDR2Mmg0djJoLTR2Mmg0djJoLTR2Mmg0djJoLTQiLz48L2c+PC9nPjwvc3ZnPg==')] opacity-20"></div>
                
                <div class="relative flex items-center justify-between">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-wider mb-2 opacity-90">Statut de la réservation</p>
                        <h3 class="text-5xl font-black mb-2 capitalize">
                            @if($reservation->statut === 'confirmée') ✓ Confirmée
                            @elseif($reservation->statut === 'en_attente') ⏳ En attente
                            @elseif($reservation->statut === 'annulée') ✕ Annulée
                            @else {{ $reservation->statut }}
                            @endif
                        </h3>
                        <p class="text-base opacity-90">
                            @if($reservation->statut === 'confirmée') Votre place est garantie !
                            @elseif($reservation->statut === 'en_attente') En cours de traitement...
                            @elseif($reservation->statut === 'annulée') Cette réservation a été annulée
                            @endif
                        </p>
                    </div>
                    <svg class="w-28 h-28 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        @if($reservation->statut === 'confirmée')
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        @elseif($reservation->statut === 'en_attente')
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        @else
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        @endif
                    </svg>
                </div>
            </div>

            {{-- Message du propriétaire (si présent) --}}
            @if($reservation->owner_message)
            <div class="mb-8 bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="text-lg font-black text-gray-900 mb-3 flex items-center gap-2">
                    <div class="p-2 bg-purple-100 rounded-lg">
                        <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path>
                        </svg>
                    </div>
                    Message du propriétaire
                </h3>
                <div class="p-4 bg-purple-50 rounded-xl border border-purple-100">
                    <p class="text-gray-800">{{ $reservation->owner_message }}</p>
                </div>
            </div>
            @endif

            {{-- Section feedback pour réservation terminée --}}
            @php
                $isCompleted = $reservation->statut === 'confirmée' && $reservation->date_fin->isPast();
                $isReservationOwner = auth()->id() === $reservation->user_id;
                $hasFeedback = $reservation->feedback !== null;
            @endphp
            
            @if($isReservationOwner && $isCompleted)
            <div class="mb-8 bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="text-lg font-black text-gray-900 mb-4 flex items-center gap-2">
                    <div class="p-2 bg-yellow-100 rounded-lg">
                        <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                        </svg>
                    </div>
                    Votre avis
                </h3>
                
                @if($hasFeedback)
                    <div class="p-4 bg-yellow-50 rounded-xl border border-yellow-100">
                        <div class="flex items-center gap-1 mb-2">
                            @for($i = 1; $i <= 5; $i++)
                                <span class="text-xl {{ $i <= $reservation->feedback->rating ? 'text-yellow-400' : 'text-gray-300' }}">★</span>
                            @endfor
                            <span class="ml-2 text-sm font-medium text-gray-600">{{ $reservation->feedback->rating }}/5</span>
                        </div>
                        @if($reservation->feedback->comment)
                            <p class="text-gray-700 mt-2">{{ $reservation->feedback->comment }}</p>
                        @endif
                        <p class="text-xs text-gray-500 mt-2">Avis donné le {{ $reservation->feedback->created_at->format('d/m/Y') }}</p>
                    </div>
                @else
                    <p class="text-gray-600 mb-4">Votre réservation est terminée. Partagez votre expérience !</p>
                    <a href="{{ route('reservations.feedback.create', $reservation) }}" 
                       class="inline-flex items-center gap-2 px-4 py-2 bg-yellow-500 text-white rounded-xl font-bold hover:bg-yellow-600 transition-all">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                        </svg>
                        Donner mon avis
                    </a>
                @endif
            </div>
            @endif

            <!-- Main Grid -->
            <div class="grid lg:grid-cols-3 gap-6">
                
                <!-- Left Column - Main Info -->
                <div class="lg:col-span-2 space-y-6">
                    
                    <!-- Parking Info Card -->
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                        <h3 class="text-xl font-black text-gray-900 mb-6 flex items-center gap-2">
                            <div class="p-2 bg-indigo-100 rounded-lg">
                                <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                </svg>
                            </div>
                            Informations de la place
                        </h3>
                        
                        <div class="space-y-4">
                            <div class="flex items-start gap-3 p-4 bg-gray-50 rounded-xl">
                                <svg class="w-5 h-5 text-indigo-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                </svg>
                                <div class="flex-1">
                                    <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Place de parking</p>
                                    <p class="text-base font-bold text-gray-900">{{ optional($reservation->place)->nom ?? 'N/A' }}</p>
                                </div>
                            </div>
                            
                            <div class="flex items-start gap-3 p-4 bg-gray-50 rounded-xl">
                                <svg class="w-5 h-5 text-indigo-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                </svg>
                                <div class="flex-1">
                                    <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Adresse</p>
                                    <p class="text-base font-medium text-gray-900">{{ optional(optional($reservation->place)->site)->adresse ?? 'N/A' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Timeline Card -->
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                        <h3 class="text-xl font-black text-gray-900 mb-6 flex items-center gap-2">
                            <div class="p-2 bg-purple-100 rounded-lg">
                                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            Période de réservation
                        </h3>

                        <div class="relative">
                            <!-- Timeline Line -->
                            <div class="absolute left-6 top-8 bottom-8 w-0.5 bg-gradient-to-b from-green-400 to-blue-400"></div>
                            
                            <!-- Start -->
                            <div class="relative flex items-start gap-4 mb-8">
                                <div class="flex-shrink-0 w-12 h-12 bg-green-500 rounded-full flex items-center justify-center shadow-lg shadow-green-200">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                                    </svg>
                                </div>
                                <div class="flex-1 bg-green-50 rounded-xl p-4">
                                    <p class="text-xs font-semibold text-green-600 uppercase mb-1">Début</p>
                                    <p class="text-lg font-black text-gray-900">{{ \Carbon\Carbon::parse($reservation->date_debut)->format('d/m/Y H:i') }}</p>
                                </div>
                            </div>
                            
                            <!-- End -->
                            <div class="relative flex items-start gap-4">
                                <div class="flex-shrink-0 w-12 h-12 bg-blue-500 rounded-full flex items-center justify-center shadow-lg shadow-blue-200">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                                    </svg>
                                </div>
                                <div class="flex-1 bg-blue-50 rounded-xl p-4">
                                    <p class="text-xs font-semibold text-blue-600 uppercase mb-1">Fin</p>
                                    <p class="text-lg font-black text-gray-900">{{ \Carbon\Carbon::parse($reservation->date_fin)->format('d/m/Y H:i') }}</p>
                                </div>
                            </div>
                        </div>
                        
                        @if($reservation->battement_minutes > 0)
                            <div class="mt-6 p-4 bg-indigo-50 rounded-xl border border-indigo-100">
                                <div class="flex items-center gap-2">
                                    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span class="text-sm font-bold text-indigo-900">Battement : {{ $reservation->battement_minutes }} minutes</span>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Right Column - Payment & User -->
                <div class="space-y-6">
                    
                    <!-- Payment Card -->
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                        <h3 class="text-lg font-black text-gray-900 mb-4 flex items-center gap-2">
                            <div class="p-2 bg-green-100 rounded-lg">
                                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            Paiement
                        </h3>
                        
                        <div class="space-y-4">
                            <div class="p-4 bg-gray-50 rounded-xl">
                                <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Montant total</p>
                                <p class="text-3xl font-black text-gray-900">{{ format_chf(($reservation->amount_cents ?? 0) / 100) }}</p>
                            </div>
                            
                            <div class="p-4 rounded-xl
                                @if(($reservation->payment_status ?? 'pending') === 'paid') bg-green-50 border border-green-200
                                @elseif(($reservation->payment_status ?? 'pending') === 'pending') bg-yellow-50 border border-yellow-200
                                @else bg-red-50 border border-red-200
                                @endif">
                                <p class="text-xs font-semibold uppercase mb-2
                                    @if(($reservation->payment_status ?? 'pending') === 'paid') text-green-600
                                    @elseif(($reservation->payment_status ?? 'pending') === 'pending') text-yellow-600
                                    @else text-red-600
                                    @endif">Statut du paiement</p>
                                <p class="text-base font-black capitalize
                                    @if(($reservation->payment_status ?? 'pending') === 'paid') text-green-900
                                    @elseif(($reservation->payment_status ?? 'pending') === 'pending') text-yellow-900
                                    @else text-red-900
                                    @endif">
                                    @if(($reservation->payment_status ?? 'pending') === 'paid') ✓ Payé
                                    @elseif(($reservation->payment_status ?? 'pending') === 'pending') ⏳ En attente
                                    @else {{ $reservation->payment_status }}
                                    @endif
                                </p>
                            </div>
                        </div>

                        @if(auth()->id() === $reservation->user_id && ($reservation->payment_status ?? 'pending') === 'pending')
                            <form method="POST" action="{{ route('reservations.payer', $reservation->id) }}" class="mt-6">
                                @csrf
                                <button type="submit" class="w-full flex items-center justify-center gap-2 py-3 bg-green-600 text-white rounded-xl font-bold hover:bg-green-700 transition-all shadow-lg shadow-green-200 hover:shadow-xl hover:shadow-green-300">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                                    </svg>
                                    Payer maintenant
                                </button>
                            </form>
                        @endif
                    </div>

                    <!-- Penalty Card -->
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                        <h3 class="text-lg font-black text-gray-900 mb-4 flex items-center gap-2">
                            <div class="p-2 bg-red-100 rounded-lg">
                                <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            Penalites de depassement
                        </h3>

                        <div class="space-y-3">
                            <div class="p-4 bg-gray-50 rounded-xl">
                                <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Regles</p>
                                <p class="text-sm text-gray-700">+1h: 40 CHF, +3h: 80 CHF, +1 jour: 120 CHF.</p>
                            </div>

                            @if($reservation->overstay_minutes > 0)
                                <div class="p-4 rounded-xl bg-red-50 border border-red-200">
                                    <p class="text-xs font-semibold text-red-600 uppercase mb-1">Depassement constate</p>
                                    <p class="text-sm font-bold text-red-900">{{ $reservation->overstay_minutes }} min</p>
                                    <p class="text-sm text-red-800 mt-1">Penalite appliquee: {{ format_chf(($reservation->penalty_cents ?? 0) / 100) }}</p>
                                </div>
                            @else
                                <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200">
                                    <p class="text-xs font-semibold text-emerald-600 uppercase mb-1">Statut</p>
                                    <p class="text-sm text-emerald-800">Aucun depassement enregistre.</p>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- User Info Card -->
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                        <h3 class="text-lg font-black text-gray-900 mb-4 flex items-center gap-2">
                            <div class="p-2 bg-blue-100 rounded-lg">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                            </div>
                            Client
                        </h3>
                        
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 bg-gradient-to-br from-blue-400 to-indigo-500 rounded-full flex items-center justify-center text-white font-black text-lg">
                                {{ substr(optional($reservation->user)->name ?? 'N', 0, 1) }}
                            </div>
                            <div>
                                <p class="font-bold text-gray-900">{{ optional($reservation->user)->name ?? 'N/A' }}</p>
                                <p class="text-sm text-gray-500">{{ optional($reservation->user)->email ?? 'N/A' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
