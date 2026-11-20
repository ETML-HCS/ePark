<section>
    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="space-y-5">
        @csrf
        @method('patch')

        <div>
            <label for="name" class="block text-sm font-semibold mb-1.5">Nom complet</label>
            <input
                id="name"
                name="name"
                type="text"
                value="{{ old('name', $user->name) }}"
                required
                autofocus
                autocomplete="name"
                class="ep-input">
            @if($errors->get('name'))
                <p class="mt-1.5 text-sm text-rose-700">{{ $errors->first('name') }}</p>
            @endif
        </div>

        <div>
            <label for="email" class="block text-sm font-semibold mb-1.5">Adresse e-mail</label>
            <input
                id="email"
                name="email"
                type="email"
                value="{{ old('email', $user->email) }}"
                required
                autocomplete="username"
                class="ep-input">
            @if($errors->get('email'))
                <p class="mt-1.5 text-sm text-rose-700">{{ $errors->first('email') }}</p>
            @endif

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div class="mt-3 p-4 rounded-xl bg-amber-50 border border-amber-200 text-sm">
                    <p class="font-semibold text-amber-900">Votre adresse e-mail n’est pas vérifiée.</p>
                    <p class="text-xs text-amber-800 mt-1">La vérification est requise pour accéder automatiquement aux groupes privés liés à votre domaine e-mail.</p>
                    <button form="send-verification" class="text-link text-sm mt-2 inline-block">
                        Renvoyer l’e-mail de vérification
                    </button>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 text-sm font-semibold text-[#166534]">
                            Un nouveau lien de vérification a été envoyé.
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div class="pt-4 border-t border-[#dce3ed]">
            <label for="quick_vehicle_plate" class="block text-sm font-semibold mb-1.5">Plaque d’immatriculation habituelle <span class="text-xs font-normal text-slate-500">(facultatif)</span></label>
            <input
                id="quick_vehicle_plate"
                name="quick_vehicle_plate"
                type="text"
                maxlength="20"
                value="{{ old('quick_vehicle_plate', $user->quick_vehicle_plate) }}"
                placeholder="Ex. : VD 123456"
                class="ep-input uppercase max-w-xs">
            <p class="mt-1 text-xs text-slate-600">Pré-remplie automatiquement lors de vos réservations et affichée au propriétaire.</p>
            @if($errors->get('quick_vehicle_plate'))
                <p class="mt-1.5 text-sm text-rose-700">{{ $errors->first('quick_vehicle_plate') }}</p>
            @endif
        </div>

        <div class="pt-4 border-t border-[#dce3ed] space-y-4">
            <div>
                <h3 class="text-sm font-bold">Coordonnées de règlement (propriétaire)</h3>
                <p class="text-xs text-slate-600 mt-0.5">Affichées aux locataires sur leurs réservations pour faciliter les paiements TWINT ou par virement.</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="twint_phone" class="block text-sm font-semibold mb-1.5">Numéro TWINT</label>
                    <input
                        id="twint_phone"
                        name="twint_phone"
                        type="text"
                        maxlength="32"
                        value="{{ old('twint_phone', $user->twint_phone) }}"
                        placeholder="Ex. : +41 79 123 45 67"
                        class="ep-input">
                    @if($errors->get('twint_phone'))
                        <p class="mt-1.5 text-sm text-rose-700">{{ $errors->first('twint_phone') }}</p>
                    @endif
                </div>
                <div>
                    <label for="iban" class="block text-sm font-semibold mb-1.5">IBAN</label>
                    <input
                        id="iban"
                        name="iban"
                        type="text"
                        maxlength="42"
                        value="{{ old('iban', $user->iban) }}"
                        placeholder="Ex. : CH93 0076 2011 6238 5295 7"
                        class="ep-input uppercase">
                    @if($errors->get('iban'))
                        <p class="mt-1.5 text-sm text-rose-700">{{ $errors->first('iban') }}</p>
                    @endif
                </div>
            </div>
            <div>
                <label for="payment_instructions" class="block text-sm font-semibold mb-1.5">Instructions complémentaires (optionnel)</label>
                <textarea
                    id="payment_instructions"
                    name="payment_instructions"
                    rows="2"
                    maxlength="500"
                    placeholder="Ex. : Merci d’indiquer le numéro de réservation en référence."
                    class="ep-input">{{ old('payment_instructions', $user->payment_instructions) }}</textarea>
                @if($errors->get('payment_instructions'))
                    <p class="mt-1.5 text-sm text-rose-700">{{ $errors->first('payment_instructions') }}</p>
                @endif
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-4 pt-2">
            <button type="submit" class="booking-primary">Enregistrer</button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 3000)"
                    class="text-sm font-semibold text-[#166534]"
                    role="status"
                >
                    Modifications enregistrées.
                </p>
            @endif
        </div>
    </form>
</section>
