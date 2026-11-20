<x-guest-layout>
    <div class="auth-panel">
        <a href="{{ route('home') }}" class="inline-flex items-center gap-2 font-bold text-xl"><x-application-logo class="h-10 w-10"/>ePark</a>
        <h1>Créer mon compte</h1><p class="text-sm text-slate-600 mb-6">Vos réservations et vos préférences au même endroit.</p>
        <form method="POST" action="{{ route('register') }}" class="space-y-4">
            @csrf
            <div><label for="name">Nom complet</label><input id="name" type="text" name="name" value="{{ old('name') }}" autocomplete="name" required autofocus><x-input-error :messages="$errors->get('name')" class="mt-1"/></div>
            <div><label for="email">Adresse e-mail</label><input id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="username" required><x-input-error :messages="$errors->get('email')" class="mt-1"/></div>
            <div><label for="password">Mot de passe</label><input id="password" type="password" name="password" autocomplete="new-password" minlength="8" required><p class="text-xs text-slate-500 mt-1">Au moins 8 caractères.</p><x-input-error :messages="$errors->get('password')" class="mt-1"/></div>
            <div><label for="password_confirmation">Confirmer le mot de passe</label><input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" required></div>
            @if($sites->isNotEmpty())<div><label for="favorite_site_id">Parking habituel <span class="font-normal text-slate-500">(facultatif)</span></label><select id="favorite_site_id" name="favorite_site_id" class="booking-select"><option value="">Choisir plus tard</option>@foreach($sites as $site)<option value="{{ $site->id }}" @selected(old('favorite_site_id') == $site->id)>{{ $site->nom }}</option>@endforeach</select><x-input-error :messages="$errors->get('favorite_site_id')" class="mt-1"/></div>@endif
            <button class="booking-primary w-full" type="submit">Créer mon compte et réserver</button>
        </form>
        <p class="text-sm mt-6 text-slate-600">Déjà inscrit ? <a href="{{ route('login') }}" class="text-link">Me connecter</a></p>
    </div>
</x-guest-layout>
