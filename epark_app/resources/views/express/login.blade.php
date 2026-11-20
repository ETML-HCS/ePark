<x-guest-layout>
    <div class="auth-panel">
        <a href="{{ route('home') }}" class="inline-flex items-center gap-2 font-bold text-xl"><x-application-logo class="h-10 w-10"/>ePark</a>
        <h1>Mon accès rapide</h1><p class="text-sm text-slate-600 mb-6">Saisissez votre clé personnelle pour retrouver vos préférences.</p>
        @if(session('success'))<p class="mb-4 text-sm text-green-800" role="status">{{ session('success') }}</p>@endif
        <form method="POST" action="{{ route('express.login') }}" class="space-y-4">
            @csrf
            <div><label for="express_key">Clé d’accès</label><input id="express_key" type="password" name="express_key" required autofocus autocomplete="off" spellcheck="false" placeholder="epk_…"><x-input-error :messages="$errors->get('express_key')" class="mt-2"/></div>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="remember_device" value="1" class="rounded border-slate-300 text-indigo-700"> Mémoriser sur cet appareil privé</label>
            <button class="booking-primary w-full" type="submit">Accéder à mes réservations</button>
        </form>
        <p class="text-xs text-slate-600 mt-4">Votre clé donne accès à votre compte. Gardez-la privée.</p>
        <div class="border-t border-slate-200 mt-6 pt-5 space-y-4 text-sm"><a href="{{ route('login') }}" class="text-link block">Me connecter par e-mail</a><a href="{{ route('register') }}" class="text-link block">Créer mon compte</a></div>
    </div>
</x-guest-layout>
