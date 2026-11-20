<section>
    <form method="post" action="{{ route('password.update') }}" class="space-y-5">
        @csrf
        @method('put')

        <div>
            <label for="update_password_current_password" class="block text-sm font-semibold mb-1.5">Mot de passe actuel</label>
            <input
                id="update_password_current_password"
                name="current_password"
                type="password"
                autocomplete="current-password"
                class="ep-input">
            @if($errors->updatePassword->get('current_password'))
                <p class="mt-1.5 text-sm text-rose-700">{{ $errors->updatePassword->first('current_password') }}</p>
            @endif
        </div>

        <div>
            <label for="update_password_password" class="block text-sm font-semibold mb-1.5">Nouveau mot de passe</label>
            <input
                id="update_password_password"
                name="password"
                type="password"
                autocomplete="new-password"
                class="ep-input">
            @if($errors->updatePassword->get('password'))
                <p class="mt-1.5 text-sm text-rose-700">{{ $errors->updatePassword->first('password') }}</p>
            @endif
            <p class="mt-1.5 text-xs text-slate-600">Minimum 8 caractères.</p>
        </div>

        <div>
            <label for="update_password_password_confirmation" class="block text-sm font-semibold mb-1.5">Confirmer le nouveau mot de passe</label>
            <input
                id="update_password_password_confirmation"
                name="password_confirmation"
                type="password"
                autocomplete="new-password"
                class="ep-input">
            @if($errors->updatePassword->get('password_confirmation'))
                <p class="mt-1.5 text-sm text-rose-700">{{ $errors->updatePassword->first('password_confirmation') }}</p>
            @endif
        </div>

        <div class="flex flex-wrap items-center gap-4 pt-2">
            <button type="submit" class="booking-primary">Mettre à jour le mot de passe</button>

            @if (session('status') === 'password-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 3000)"
                    class="text-sm font-semibold text-[#166534]"
                    role="status"
                >
                    Mot de passe modifié.
                </p>
            @endif
        </div>
    </form>
</section>
