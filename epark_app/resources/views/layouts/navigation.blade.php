@php
    $user = auth()->user();
    $links = $user ? [
        ['label' => 'Réserver', 'route' => 'express.index', 'active' => request()->routeIs('express.*', 'reservations.create')],
        ['label' => 'Réservations', 'route' => 'reservations.index', 'active' => request()->routeIs('reservations.*') && ! request()->routeIs('reservations.create')],
        ['label' => 'Activité', 'route' => 'dashboard', 'active' => request()->routeIs('dashboard')],
        ['label' => 'Mes places', 'route' => 'places.mes', 'active' => request()->routeIs('places.*', 'sites.*')],
    ] : [];
    $unreadCount = $user?->unreadNotifications()->count() ?? 0;
@endphp
<nav class="bg-white border-b border-gray-200 sticky top-0 z-40" aria-label="Navigation principale">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex h-16 items-center gap-6">
        <a href="{{ $user ? route('express.index') : route('home') }}" class="inline-flex items-center gap-2 shrink-0" aria-label="ePark, accueil"><x-application-logo class="h-9 w-9"/><span class="text-xl font-bold">ePark</span></a>
        @auth
            <div class="hidden md:flex items-center gap-1 flex-1">
                @foreach($links as $link)
                    <a href="{{ route($link['route']) }}" @if($link['active']) aria-current="page" @endif class="px-3 py-2 rounded-lg text-sm font-semibold {{ $link['active'] ? 'text-indigo-800 bg-indigo-50' : 'text-slate-600 hover:bg-slate-50' }}">{{ $link['label'] }}</a>
                @endforeach
            </div>
            <div class="flex items-center gap-3 ml-auto">
                <a href="{{ route('notifications.index') }}" aria-label="Notifications{{ $unreadCount ? ', '.$unreadCount.' non lues' : '' }}" class="text-sm font-semibold text-slate-600 py-3"><span aria-hidden="true">Notifications{{ $unreadCount ? ' ('.$unreadCount.')' : '' }}</span></a>
                <details class="relative">
                    <summary class="cursor-pointer list-none inline-flex items-center gap-1.5 text-sm font-semibold px-3 py-2 rounded-lg border border-slate-200 hover:bg-slate-50">
                        <span>{{ explode(' ', $user->name)[0] }}</span>
                        <span aria-hidden="true" class="text-xs text-slate-400">▾</span>
                    </summary>
                    <div class="absolute right-0 mt-3 w-56 bg-white border border-slate-200 rounded-xl shadow-lg p-2 grid gap-1">
                        <a href="{{ route('profile.edit') }}" class="p-3 text-sm rounded-lg hover:bg-slate-50">Mon profil et mes groupes</a>
                        <a href="{{ route('places.create') }}" class="p-3 text-sm font-semibold text-indigo-700 rounded-lg hover:bg-indigo-50">+ Proposer une place</a>
                        <a href="{{ route('places.mes') }}" class="p-3 text-sm rounded-lg hover:bg-slate-50">Mes places &amp; sites</a>
                        <a href="{{ route('home') }}" class="p-3 text-sm rounded-lg hover:bg-slate-50">Tous les parkings</a>
                        @if($user->role === 'admin')<a href="{{ route('admin.dashboard') }}" class="p-3 text-sm rounded-lg hover:bg-slate-50">Administration</a>@endif
                        <form method="POST" action="{{ route('logout') }}">@csrf<button class="w-full text-left p-3 text-sm text-red-700 rounded-lg hover:bg-red-50" type="submit">Déconnexion</button></form>
                    </div>
                </details>
            </div>
        @else
            <div class="flex gap-4 items-center ml-auto text-sm"><a href="{{ route('login') }}" class="text-link">Connexion</a><a href="{{ route('register') }}" class="booking-primary">Créer un compte</a></div>
        @endauth
    </div>
</nav>
@auth
    <nav class="mobile-nav" aria-label="Navigation mobile">
        @foreach($links as $link)<a href="{{ route($link['route']) }}" @if($link['active']) aria-current="page" @endif>{{ $link['label'] }}</a>@endforeach
    </nav>
@endauth
