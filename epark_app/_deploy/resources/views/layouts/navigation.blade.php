{{-- 
  Composant de Navigation 
  Assure-toi d'avoir Alpine.js chargé (normalement via Vite dans app.js)
--}}
<nav x-data="{ open: false }" 
     class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 sticky top-0 z-50 transition-colors duration-300">
     
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            
            <!-- Section Gauche : Logo -->
            <div class="flex items-center gap-4">
                <!-- Logo -->
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2 group focus:outline-none focus:ring-2 focus:ring-indigo-500 rounded-lg">
                    <div class="bg-indigo-600 text-white p-1.5 rounded-lg group-hover:bg-indigo-700 transition-colors shadow-sm">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M6 3a3 3 0 00-3 3v12a3 3 0 003 3h12a3 3 0 003-3V6a3 3 0 00-3-3H6zm4 5h2.5a2.5 2.5 0 010 5H12v3h-2V8z"/>
                            <path d="M12 10v3h.5a1.5 1.5 0 000-3H12z"/>
                        </svg>
                    </div>
                    <span class="font-bold text-xl tracking-tight text-gray-900 dark:text-white hidden sm:block group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">
                        ePark
                    </span>
                </a>

                <!-- Navigation Links (Desktop) -->
                <div class="hidden sm:flex sm:space-x-1">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        {{ __('Tableau de bord') }}
                    </x-nav-link>
                    <x-nav-link :href="route('reservations.index')" :active="request()->routeIs('reservations.index')">
                        {{ __('Réservations') }}
                    </x-nav-link>
                    @auth
                        @if(Auth::user()->role === 'proprietaire' || Auth::user()->role === 'les deux')
                            <x-nav-link :href="route('places.mes')" :active="request()->routeIs('places.mes')">
                                {{ __('Mes places') }}
                            </x-nav-link>
                        @endif
                    @endauth
                    @auth
                        @if(Auth::user()->role === 'admin')
                            <x-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.dashboard')">
                                {{ __('Back-office') }}
                            </x-nav-link>
                        @endif
                    @endauth
                </div>
            </div>

            <!-- Section Droite : Dropdown Profil -->
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                @auth
                    @php
                        $unreadCount = Auth::user()->unreadNotifications()->count();
                        $notifications = Auth::user()->notifications()->latest()->take(5)->get();
                    @endphp

                    <x-dropdown align="right" width="72">
                        <x-slot name="trigger">
                            <button class="relative inline-flex items-center justify-center h-10 w-10 rounded-lg text-gray-600 dark:text-gray-200 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition-all">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0a3 3 0 11-6 0h6z" />
                                </svg>
                                @if($unreadCount > 0)
                                    <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs font-bold rounded-full h-5 min-w-[20px] px-1.5 flex items-center justify-center">
                                        {{ $unreadCount }}
                                    </span>
                                @endif
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            <div class="block px-4 py-3 border-b border-gray-100 dark:border-gray-700">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <div class="text-sm font-semibold text-gray-900 dark:text-white">Notifications</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">Dernieres 5</div>
                                    </div>
                                    @if($unreadCount > 0)
                                        <form method="POST" action="{{ route('notifications.markAllRead') }}">
                                            @csrf
                                            <button type="submit" class="text-xs font-semibold text-indigo-600 hover:text-indigo-500">Tout marquer lu</button>
                                        </form>
                                    @endif
                                </div>
                            </div>

                            @if($notifications->isEmpty())
                                <div class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                                    Aucune notification pour le moment.
                                </div>
                            @else
                                @foreach($notifications as $notification)
                                    @php
                                        $payload = $notification->data ?? [];
                                        $isEndReminder = ($payload['type'] ?? null) === 'end_reminder';
                                        $placeName = $payload['place_name'] ?? '';
                                        $title = $isEndReminder ? 'Fin dans 15 min' : 'Notification';
                                        $message = $placeName ? $placeName : 'Reservation en cours';
                                    @endphp

                                    <a href="{{ url('/reservations/' . ($payload['reservation_id'] ?? '')) }}"
                                       class="block px-4 py-3 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors {{ $notification->read_at ? '' : 'bg-indigo-50/50 dark:bg-indigo-900/20' }}">
                                        <div class="font-medium text-gray-900 dark:text-white">{{ $title }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ $message }}</div>
                                    </a>
                                @endforeach
                            @endif
                        </x-slot>
                    </x-dropdown>

                    <x-dropdown align="right" width="56">
                        <x-slot name="trigger">
                            <button class="flex items-center gap-3 text-sm font-medium text-gray-700 dark:text-gray-200 hover:text-indigo-600 dark:hover:text-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition-all duration-200 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 px-3 py-2">
                                
                                <!-- Avatar Initiales -->
                                <div class="h-8 w-8 rounded-full bg-indigo-100 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300 flex items-center justify-center text-sm font-bold ring-2 ring-white dark:ring-gray-800 transition-all">
                                    {{ substr(Auth::user()->name, 0, 1) }}{{ substr(explode(' ', Auth::user()->name)[1] ?? '', 0, 1) }}
                                </div>
                                
                                <!-- Nom Utilisateur -->
                                <span class="hidden md:block">{{ Auth::user()->name }}</span>

                                <!-- Flèche -->
                                <svg class="fill-current h-4 w-4 text-gray-400" xmlns="http://www.w3.org/2000/svg " viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            <!-- Info User -->
                            <div class="block px-4 py-3 border-b border-gray-100 dark:border-gray-700">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">{{ Auth::user()->name }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 truncate max-w-[200px]">{{ Auth::user()->email }}</div>
                            </div>

                            <!-- Lien Profil -->
                            <x-dropdown-link :href="route('profile.edit')">
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                    {{ __('Mon Profil') }}
                                </div>
                            </x-dropdown-link>

                            <!-- Logout -->
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <x-dropdown-link 
                                    as="button" 
                                    class="w-full text-left text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 focus:text-red-700"
                                >
                                    <div class="flex items-center gap-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                                        {{ __('Déconnexion') }}
                                    </div>
                                </x-dropdown-link>
                            </form>
                        </x-slot>
                    </x-dropdown>
                @else
                    <!-- Bouton Login simple si non connecté -->
                    <a href="{{ route('login') }}" class="text-sm font-medium text-gray-700 dark:text-gray-200 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
                        Connexion
                    </a>
                @endauth
            </div>

            <!-- Hamburger Menu (Mobile) -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" type="button" class="inline-flex items-center justify-center p-2 rounded-md text-gray-500 dark:text-gray-200 hover:text-gray-700 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-500 transition duration-150 ease-in-out" aria-expanded="false">
                    <span class="sr-only">Ouvrir le menu</span>
                    
                    <!-- Icone Menu Hamburger (visible quand open = false) -->
                    <svg x-show="!open" x-cloak class="h-6 w-6" xmlns="http://www.w3.org/2000/svg " fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                    
                    <!-- Icone Fermer (visible quand open = true) -->
                    <svg x-show="open" x-cloak class="h-6 w-6" xmlns="http://www.w3.org/2000/svg " fill="none" viewBox="0 0 24 24" stroke="currentColor" style="display: none;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
        <div x-show="open" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-2"
            x-cloak
            class="sm:hidden border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
         
        <div class="pt-3 pb-3 px-4 flex items-center gap-2 overflow-x-auto">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" class="inline-flex w-auto border-l-0 px-3 py-2 items-center justify-center">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                <span class="sr-only">{{ __('Tableau de bord') }}</span>
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('reservations.index')" :active="request()->routeIs('reservations.index')" class="inline-flex w-auto border-l-0 px-3 py-2 items-center justify-center">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10m-12 8h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
                <span class="sr-only">{{ __('Réservations') }}</span>
            </x-responsive-nav-link>
            @auth
                @if(Auth::user()->role === 'proprietaire' || Auth::user()->role === 'les deux')
                    <x-responsive-nav-link :href="route('places.mes')" :active="request()->routeIs('places.mes')" class="inline-flex w-auto border-l-0 px-3 py-2 items-center justify-center">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <span class="sr-only">{{ __('Mes places') }}</span>
                    </x-responsive-nav-link>
                @endif
            @endauth
            @auth
                @if(Auth::user()->role === 'admin')
                    <x-responsive-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.dashboard')" class="inline-flex w-auto border-l-0 px-3 py-2 items-center justify-center">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <span class="sr-only">{{ __('Back-office') }}</span>
                    </x-responsive-nav-link>
                @endif
            @endauth
            @auth
                @php
                    $mobileUnreadCount = Auth::user()->unreadNotifications()->count();
                @endphp
                <a href="{{ route('notifications.index') }}" class="relative inline-flex w-auto border-l-0 px-3 py-2 items-center justify-center text-gray-600 dark:text-gray-200 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-gray-50 dark:hover:bg-gray-700 transition-all" aria-label="Notifications">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0a3 3 0 11-6 0h6z" />
                    </svg>
                    @if($mobileUnreadCount > 0)
                        <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs font-bold rounded-full h-5 min-w-[20px] px-1.5 flex items-center justify-center">
                            {{ $mobileUnreadCount }}
                        </span>
                    @endif
                </a>
                <x-responsive-nav-link :href="route('profile.edit')" class="inline-flex w-auto border-l-0 px-3 py-2 items-center justify-center">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    <span class="sr-only">{{ __('Mon Profil') }}</span>
                </x-responsive-nav-link>
                <form method="POST" action="{{ route('logout') }}" class="inline-flex">
                    @csrf
                    <x-responsive-nav-link as="button" class="inline-flex w-auto border-l-0 px-3 py-2 items-center justify-center text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        <span class="sr-only">{{ __('Déconnexion') }}</span>
                    </x-responsive-nav-link>
                </form>
            @endauth
            @guest
                <a href="{{ route('login') }}" class="inline-flex w-auto border-l-0 px-3 py-2 items-center justify-center text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 rounded-lg transition-all" aria-label="Connexion">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 3h4a2 2 0 012 2v14a2 2 0 01-2 2h-4m-5-4l5-5-5-5m5 5H3" />
                    </svg>
                    <span class="sr-only">Connexion</span>
                </a>
            @endguest
        </div>
    </div>
</nav>