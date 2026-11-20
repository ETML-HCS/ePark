<x-guest-layout>
    <div class="auth-panel">
        <a href="{{ route('home') }}" class="inline-flex items-center gap-2 font-bold text-xl"><x-application-logo class="h-10 w-10"/>ePark</a>
        <h1>Connexion</h1><p class="text-sm text-slate-600 mb-6">Retrouvez votre place et vos réservations.</p>
        <x-auth-session-status class="mb-4" :status="session('status')" />
        <form method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf
            <div><label for="email">Adresse e-mail</label><input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"><x-input-error :messages="$errors->get('email')" class="mt-2"/></div>
            <div><div class="flex justify-between items-center"><label for="password">Mot de passe</label><a href="{{ route('password.request') }}" class="text-link text-xs mb-1">Mot de passe oublié ?</a></div><input id="password" type="password" name="password" required autocomplete="current-password"><x-input-error :messages="$errors->get('password')" class="mt-2"/></div>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="remember" value="1" class="rounded border-slate-300 text-indigo-700"> Rester connecté sur cet appareil</label>
            <button class="booking-primary w-full" type="submit">Me connecter</button>
        </form>
        <div class="border-t border-slate-200 mt-6 pt-5 space-y-4 text-sm"><a href="{{ route('express.index') }}" class="text-link block">J’ai une clé d’accès rapide</a><p class="text-slate-600">Première visite ? <a href="{{ route('register') }}" class="text-link">Créer mon compte</a></p></div>
    </div>
</x-guest-layout>
