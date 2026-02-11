<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-black text-2xl text-gray-900 flex items-center gap-3">
                <div class="p-2 bg-indigo-100 rounded-xl">
                    <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
                Mes Réservations
            </h2>
            <a href="{{ route('reservations.create') }}" aria-label="Nouvelle reservation" class="inline-flex items-center justify-center sm:justify-start gap-0 sm:gap-2 px-3 sm:px-6 py-3 bg-indigo-600 text-white rounded-xl font-bold hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-200 hover:shadow-xl hover:shadow-indigo-300 transform hover:-translate-y-0.5">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                <span class="hidden sm:inline">Nouvelle réservation</span>
            </a>
        </div>
    </x-slot>

    <div class="py-8 bg-gradient-to-b from-gray-50 to-white min-h-screen">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <!-- Filtres -->
            <div class="mb-6 bg-white rounded-2xl shadow-sm border border-gray-100 p-5" x-data="{ showFilters: false }">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <button @click="showFilters = !showFilters" class="flex items-center gap-2 px-4 py-2 rounded-xl border-2 font-bold transition-all" :class="showFilters ? 'bg-indigo-50 text-indigo-600 border-indigo-200' : 'bg-white text-gray-600 border-gray-200 hover:border-gray-300'">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                            </svg>
                            Filtrer
                        </button>
                        <div class="text-sm text-gray-500">
                            <span class="font-bold text-gray-900">{{ $reservations->count() }}</span> réservation(s)
                        </div>
                    </div>
                </div>
                <form method="GET" action="{{ route('reservations.index') }}" x-show="showFilters" x-transition class="mt-4 pt-4 border-t border-gray-100 grid grid-cols-1 md:grid-cols-5 gap-4">
                    <select name="period" class="rounded-xl border-gray-200 focus:ring-2 focus:ring-indigo-500">
                        <option value="upcoming" {{ request('period', 'upcoming') === 'upcoming' ? 'selected' : '' }}>Période : À venir</option>
                        <option value="past" {{ request('period') === 'past' ? 'selected' : '' }}>Période : Passées</option>
                        <option value="all" {{ request('period') === 'all' ? 'selected' : '' }}>Période : Toutes</option>
                    </select>
                    <select name="status" class="rounded-xl border-gray-200 focus:ring-2 focus:ring-indigo-500">
                        <option value="" {{ request('status') === null || request('status') === '' ? 'selected' : '' }}>Statut : Tous</option>
                        <option value="en_attente" {{ request('status') === 'en_attente' ? 'selected' : '' }}>En attente</option>
                        <option value="confirmée" {{ request('status') === 'confirmée' ? 'selected' : '' }}>Confirmée</option>
                        <option value="annulée" {{ request('status') === 'annulée' ? 'selected' : '' }}>Annulée</option>
                        <option value="terminee" {{ request('status') === 'terminee' ? 'selected' : '' }}>Terminée</option>
                    </select>
                    <select name="payment" class="rounded-xl border-gray-200 focus:ring-2 focus:ring-indigo-500">
                        <option value="" {{ request('payment') === null || request('payment') === '' ? 'selected' : '' }}>Paiement : Tous</option>
                        <option value="pending" {{ request('payment') === 'pending' ? 'selected' : '' }}>En attente</option>
                        <option value="paid" {{ request('payment') === 'paid' ? 'selected' : '' }}>Payé</option>
                        <option value="failed" {{ request('payment') === 'failed' ? 'selected' : '' }}>Echec</option>
                        <option value="refunded" {{ request('payment') === 'refunded' ? 'selected' : '' }}>Remboursé</option>
                    </select>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-xl font-bold hover:bg-indigo-700 transition-all">Appliquer</button>
                    <a href="{{ route('reservations.index') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-xl font-bold hover:bg-gray-200 transition-all text-center">Réinitialiser</a>
                </form>
            </div>

            <!-- Liste des réservations en cartes -->
            <div class="space-y-4">
                @forelse($reservations as $reservation)
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 hover:shadow-lg transition-all duration-300 overflow-hidden group">
                        <div class="p-6">
                            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                                <!-- Info principale -->
                                <div class="flex-1">
                                    <div class="flex items-start gap-4">
                                        <div class="p-3 bg-gradient-to-br from-indigo-400 to-purple-500 rounded-2xl shadow-lg flex-shrink-0">
                                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                            </svg>
                                        </div>
                                        <div class="flex-1">
                                            <h3 class="text-lg font-black text-gray-900 mb-1">#{{ $reservation->id }} - {{ optional(optional($reservation->place)->site)->nom ?? 'Site N/A' }}</h3>
                                            <p class="text-sm text-gray-500 flex items-center gap-1">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                                </svg>
                                                {{ optional(optional($reservation->place)->site)->adresse ?? 'Adresse N/A' }}
                                            </p>
                                            <div class="flex items-center gap-4 mt-3 text-xs">
                                                <div class="flex items-center gap-1 text-gray-600">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                    </svg>
                                                    <span class="font-medium">{{ \Carbon\Carbon::parse($reservation->date_debut)->format('d M Y') }}</span>
                                                </div>
                                                <div class="flex items-center gap-1 text-gray-600">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                    </svg>
                                                    <span class="font-medium">{{ \Carbon\Carbon::parse($reservation->date_debut)->format('H:i') }} - {{ \Carbon\Carbon::parse($reservation->date_fin)->format('H:i') }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Badges & Actions -->
                                <div class="flex flex-col items-end gap-3">
                                    <div class="flex items-center gap-2">
                                        @php
                                            $isCompleted = $reservation->statut === 'confirmée'
                                                && $reservation->date_fin
                                                && \Carbon\Carbon::parse($reservation->date_fin)->isPast();
                                        @endphp
                                        @if($isCompleted)
                                            <span class="px-3 py-1.5 bg-blue-100 text-blue-700 rounded-xl text-xs font-black uppercase tracking-wider">✓ Terminée</span>
                                        @elseif($reservation->statut === 'confirmée')
                                            <span class="px-3 py-1.5 bg-green-100 text-green-700 rounded-xl text-xs font-black uppercase tracking-wider">✓ Confirmée</span>
                                        @elseif($reservation->statut === 'en_attente')
                                            <span class="px-3 py-1.5 bg-yellow-100 text-yellow-700 rounded-xl text-xs font-black uppercase tracking-wider">⏱ En attente</span>
                                        @elseif($reservation->statut === 'annulée')
                                            <span class="px-3 py-1.5 bg-red-100 text-red-700 rounded-xl text-xs font-black uppercase tracking-wider">✕ Annulée</span>
                                        @else
                                            <span class="px-3 py-1.5 bg-gray-100 text-gray-700 rounded-xl text-xs font-black uppercase tracking-wider">{{ $reservation->statut }}</span>
                                        @endif

                                        @if($reservation->payment_status === 'paid')
                                            <span class="px-3 py-1.5 bg-emerald-100 text-emerald-700 rounded-xl text-xs font-black">💳 Payé</span>
                                        @elseif($reservation->payment_status === 'failed')
                                            <span class="px-3 py-1.5 bg-red-100 text-red-700 rounded-xl text-xs font-black">💳 Échec</span>
                                        @elseif($reservation->payment_status === 'refunded')
                                            <span class="px-3 py-1.5 bg-orange-100 text-orange-700 rounded-xl text-xs font-black">💰 Remboursé</span>
                                        @else
                                            <span class="px-3 py-1.5 bg-gray-100 text-gray-700 rounded-xl text-xs font-black">💳 En attente</span>
                                        @endif
                                    </div>

                                    <!-- Boutons d'action -->
                                    <div class="flex items-center gap-2">
                                        <a href="{{ route('reservations.show', $reservation->id) }}" class="px-4 py-2 bg-gray-50 text-gray-700 rounded-xl text-xs font-bold hover:bg-gray-100 transition-all">
                                            Détails
                                        </a>

                                        @if($reservation->user_id === auth()->id() && $reservation->payment_status === 'pending')
                                            <form method="POST" action="{{ route('reservations.payer', $reservation->id) }}" class="inline-block">
                                                @csrf
                                                <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-xl text-xs font-bold hover:bg-green-700 transition-all shadow-sm">
                                                    💳 Payer
                                                </button>
                                            </form>
                                        @endif

                                        @if($reservation->user_id === auth()->id() && $reservation->statut !== 'annulée')
                                            <form method="POST" action="{{ route('reservations.destroy', $reservation->id) }}" onsubmit="return confirm('Annuler cette réservation ?');" class="inline-block">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="px-4 py-2 bg-red-50 text-red-600 rounded-xl text-xs font-bold hover:bg-red-100 transition-all">
                                                    Annuler
                                                </button>
                                            </form>
                                        @endif

                                        @if(isset($reservation->place->site) && $reservation->place->site->user_id === auth()->id() && $reservation->statut === 'en_attente')
                                            <div x-data="{ showConfirmModal: false, showRefuseModal: false }">
                                                {{-- Bouton Valider --}}
                                                <button @click="showConfirmModal = true" type="button" class="px-4 py-2 bg-green-600 text-white rounded-xl text-xs font-bold hover:bg-green-700 transition-all">
                                                    ✓ Valider
                                                </button>
                                                {{-- Bouton Refuser --}}
                                                <button @click="showRefuseModal = true" type="button" class="px-4 py-2 bg-orange-600 text-white rounded-xl text-xs font-bold hover:bg-orange-700 transition-all">
                                                    ✕ Refuser
                                                </button>

                                                {{-- Modal Valider --}}
                                                <div x-show="showConfirmModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
                                                    <div class="flex min-h-screen items-center justify-center p-4">
                                                        <div x-show="showConfirmModal" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showConfirmModal = false"></div>
                                                        <div x-show="showConfirmModal" class="relative bg-white rounded-2xl shadow-xl max-w-md w-full p-6 transform transition-all">
                                                            <h3 class="text-lg font-bold text-gray-900 mb-4">Confirmer la réservation</h3>
                                                            <form method="POST" action="{{ route('reservations.valider', $reservation->id) }}">
                                                                @csrf
                                                                <div class="mb-4">
                                                                    <label for="owner_message_confirm_{{ $reservation->id }}" class="block text-sm font-medium text-gray-700 mb-1">Message pour le locataire (optionnel)</label>
                                                                    <textarea id="owner_message_confirm_{{ $reservation->id }}" name="owner_message" rows="3" class="w-full rounded-xl border-2 border-gray-200 bg-gray-50 focus:border-green-500 focus:ring-0 text-sm" placeholder="Ex: Code portail 1234, place à gauche..."></textarea>
                                                                </div>
                                                                <div class="flex justify-end gap-3">
                                                                    <button type="button" @click="showConfirmModal = false" class="px-4 py-2 text-gray-700 bg-gray-100 rounded-xl font-semibold hover:bg-gray-200 transition-colors">Annuler</button>
                                                                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-xl font-bold hover:bg-green-700 transition-colors">✓ Confirmer</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>

                                                {{-- Modal Refuser --}}
                                                <div x-show="showRefuseModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
                                                    <div class="flex min-h-screen items-center justify-center p-4">
                                                        <div x-show="showRefuseModal" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showRefuseModal = false"></div>
                                                        <div x-show="showRefuseModal" class="relative bg-white rounded-2xl shadow-xl max-w-md w-full p-6 transform transition-all">
                                                            <h3 class="text-lg font-bold text-gray-900 mb-4">Refuser la réservation</h3>
                                                            <form method="POST" action="{{ route('reservations.refuser', $reservation->id) }}">
                                                                @csrf
                                                                <div class="mb-4">
                                                                    <label for="owner_message_refuse_{{ $reservation->id }}" class="block text-sm font-medium text-gray-700 mb-1">Raison du refus (optionnel)</label>
                                                                    <textarea id="owner_message_refuse_{{ $reservation->id }}" name="owner_message" rows="3" class="w-full rounded-xl border-2 border-gray-200 bg-gray-50 focus:border-orange-500 focus:ring-0 text-sm" placeholder="Ex: Place non disponible, travaux en cours..."></textarea>
                                                                </div>
                                                                <div class="flex justify-end gap-3">
                                                                    <button type="button" @click="showRefuseModal = false" class="px-4 py-2 text-gray-700 bg-gray-100 rounded-xl font-semibold hover:bg-gray-200 transition-colors">Annuler</button>
                                                                    <button type="submit" class="px-4 py-2 bg-orange-600 text-white rounded-xl font-bold hover:bg-orange-700 transition-colors">✕ Refuser</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="bg-white rounded-2xl border-2 border-dashed border-gray-200 p-12 text-center">
                        <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-2">Aucune réservation</h3>
                        <p class="text-gray-500 mb-6">Vous n'avez pas encore de réservation. Commencez par réserver une place !</p>
                        <a href="{{ route('reservations.create') }}" class="inline-flex items-center gap-2 px-6 py-3 bg-indigo-600 text-white rounded-xl font-bold hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-200">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            Réserver maintenant
                        </a>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
