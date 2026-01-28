<nav x-data="{ open: false }" class="bg-white border-b border-gray-200 sticky top-0 z-50 transition-shadow duration-300 shadow-sm hover:shadow-md">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            
            <!-- Section Gauche : Logo -->
            <div class="flex">
                <div class="shrink-0 flex items-center gap-2">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-2 group">
                        <!-- Icône Parking -->
                        <div class="bg-indigo-600 text-white p-1.5 rounded-lg group-hover:bg-indigo-700 transition-colors shadow-sm">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                        </div>
                        <!-- Nom App -->
                        <span class="font-bold text-xl tracking-tight text-gray-900 hidden sm:block">ePark</span>
                    </a>
                </div>

                <!-- Navigation Links (Desktop) -->
                <div class="hidden sm:ms-10 sm:flex sm:space-x-2 sm:items-center">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        {{ __('Tableau de bord') }}
                    </x-nav-link>
                    <x-nav-link :href="route('reservations.index')" :active="request()->routeIs('reservations.index')">
                        {{ __('Réservations') }}
                    </x-nav-link>
                </div>
            </div>

            <!-- Section Droite : Dropdown Profil -->
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <x-dropdown align="right" width="56">
                    <x-slot name="trigger">
                        <button class="flex items-center gap-3 text-sm font-medium text-gray-700 hover:text-indigo-600 focus:outline-none transition-colors duration-200 px-3 py-2 rounded-full hover:bg-gray-50 border border-transparent hover:border-gray-200">
                            
                            <!-- Avatar Initiales -->
                            @if(Auth::check())
                                <div class="h-8 w-8 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-sm font-bold ring-2 ring-white">
                                    {{ substr(Auth::user()->name, 0, 1) }}{{ substr(explode(' ', Auth::user()->name)[1] ?? '', 0, 1) }}
                                </div>
                                <!-- Nom Utilisateur -->
                                <span class="hidden md:block">{{ Auth::user()->name }}</span>
                            @else
                                <div class="h-8 w-8 rounded-full bg-gray-200 text-gray-400 flex items-center justify-center text-sm font-bold ring-2 ring-white">
                                    ?
                                </div>
                                <span class="hidden md:block">Invité</span>
                            @endif

                            <!-- Flèche -->
                            <svg class="fill-current h-4 w-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <!-- Info User (En-tête dropdown) -->
                        <div class="block px-4 py-3 border-b border-gray-100">
                            @if(Auth::check())
                                <div class="text-sm font-medium text-gray-900">{{ Auth::user()->name }}</div>
                                <div class="text-xs text-gray-500 truncate">{{ Auth::user()->email }}</div>
                            @else
                                <div class="text-sm font-medium text-gray-900">Invité</div>
                                <div class="text-xs text-gray-500 truncate">&nbsp;</div>
                            @endif
                        </div>

                        <x-dropdown-link :href="route('profile.edit')">
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                Mon Profil
                            </div>
                        </x-dropdown-link>

                        <!-- Logout -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link 
                                as="button" 
                                class="w-full text-left text-red-600 hover:bg-red-50 focus:text-red-700"
                                onclick="event.preventDefault(); this.closest('form').submit();"
                            >
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                                    Déconnexion
                                </div>
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger Menu (Mobile) -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-500 hover:text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-500 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden border-b border-gray-200 bg-gray-50">
        <div class="pt-2 pb-3 space-y-1 px-4">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                {{ __('Tableau de bord') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('reservations.index')" :active="request()->routeIs('reservations.index')">
                {{ __('Réservations') }}
            </x-responsive-nav-link>
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-4 border-t border-gray-200">
            <div class="flex items-center px-4">
                <div class="shrink-0">
                    @if(Auth::check())
                        <div class="h-10 w-10 rounded-full bg-indigo-600 flex items-center justify-center text-white font-bold">
                            {{ substr(Auth::user()->name, 0, 1) }}
                        </div>
                    @else
                        <div class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center text-gray-400 font-bold">?</div>
                    @endif
                </div>
                <div class="ms-3">
                    @if(Auth::check())
                        <div class="text-base font-medium text-gray-800">{{ Auth::user()->name }}</div>
                        <div class="text-sm font-medium text-gray-500">{{ Auth::user()->email }}</div>
                    @else
                        <div class="text-base font-medium text-gray-800">Invité</div>
                        <div class="text-sm font-medium text-gray-500">&nbsp;</div>
                    @endif
                </div>
            </div>

            <div class="mt-3 space-y-1 px-2">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Mon Profil') }}
                </x-responsive-nav-link>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-responsive-nav-link 
                        as="button" 
                        class="w-full text-left text-red-600 hover:bg-red-50"
                        onclick="event.preventDefault(); this.closest('form').submit();"
                    >
                        {{ __('Déconnexion') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>