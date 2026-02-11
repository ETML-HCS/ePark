<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-black text-2xl text-gray-900 dark:text-gray-100 flex items-center gap-3">
                <div class="p-2 bg-indigo-100 dark:bg-indigo-900/50 rounded-xl">
                    <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0a3 3 0 11-6 0h6z" />
                    </svg>
                </div>
                Notifications
            </h2>
            @php $unreadTotal = Auth::user()->unreadNotifications()->count(); @endphp
            @if($unreadTotal > 0)
                <form method="POST" action="{{ route('notifications.markAllRead') }}">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl text-sm font-bold text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 transition-all shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <span class="hidden sm:inline">Tout marquer lu</span>
                    </button>
                </form>
            @endif
        </div>
    </x-slot>

    <div class="py-8 bg-gradient-to-b from-gray-50 to-white dark:from-gray-900 dark:to-gray-900 min-h-screen">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            @if($notifications->isEmpty())
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-2xl border border-gray-100 dark:border-gray-700 p-12 text-center">
                    <div class="flex flex-col items-center">
                        <div class="p-4 bg-gray-100 dark:bg-gray-700 rounded-2xl mb-4">
                            <svg class="w-12 h-12 text-gray-300 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0a3 3 0 11-6 0h6z" />
                            </svg>
                        </div>
                        <p class="text-lg font-semibold text-gray-900 dark:text-white mb-1">Aucune notification</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Vous serez notifié lors de nouvelles réservations ou rappels.</p>
                    </div>
                </div>
            @else
                <div class="space-y-3">
                    @foreach($notifications as $notification)
                        @php
                            $payload = $notification->data ?? [];
                            $isEndReminder = ($payload['type'] ?? null) === 'end_reminder';
                            $isNewReservation = ($payload['type'] ?? null) === 'new_reservation';
                            $placeName = $payload['place_name'] ?? '';
                            $isUnread = is_null($notification->read_at);
                        @endphp
                        <a href="{{ url('/reservations/' . ($payload['reservation_id'] ?? '')) }}"
                           class="group block bg-white dark:bg-gray-800 rounded-2xl border shadow-sm hover:shadow-md transition-all duration-200 overflow-hidden {{ $isUnread ? 'border-indigo-200 dark:border-indigo-700 ring-1 ring-indigo-100 dark:ring-indigo-900/30' : 'border-gray-100 dark:border-gray-700' }}">
                            <div class="flex items-start gap-4 p-5">
                                {{-- Icône contextuelle --}}
                                <div class="flex-shrink-0 p-2.5 rounded-xl {{ $isEndReminder ? 'bg-amber-100 dark:bg-amber-900/30' : ($isNewReservation ? 'bg-green-100 dark:bg-green-900/30' : 'bg-indigo-100 dark:bg-indigo-900/30') }}">
                                    @if($isEndReminder)
                                        <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    @elseif($isNewReservation)
                                        <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                    @else
                                        <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0a3 3 0 11-6 0h6z" />
                                        </svg>
                                    @endif
                                </div>

                                {{-- Contenu --}}
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2">
                                        <p class="text-sm font-semibold text-gray-900 dark:text-white">
                                            @if($isEndReminder) Fin dans 15 min
                                            @elseif($isNewReservation) Nouvelle réservation
                                            @else Notification
                                            @endif
                                        </p>
                                        @if($isUnread)
                                            <span class="flex-shrink-0 w-2 h-2 bg-indigo-500 rounded-full"></span>
                                        @endif
                                    </div>
                                    @if($placeName)
                                        <p class="text-sm text-gray-600 dark:text-gray-300 mt-0.5 truncate">{{ $placeName }}</p>
                                    @endif
                                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">{{ $notification->created_at->diffForHumans() }}</p>
                                </div>

                                {{-- Flèche --}}
                                <div class="flex-shrink-0 self-center">
                                    <svg class="w-5 h-5 text-gray-300 dark:text-gray-600 group-hover:text-indigo-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
