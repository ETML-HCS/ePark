<section class="space-y-4">
    <p class="text-sm text-slate-600">
        Une fois votre compte supprimé, toutes vos données seront définitivement effacées. La suppression est bloquée tant que des réservations sont actives ou à venir.
    </p>

    <button
        type="button"
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
        class="booking-danger"
    >
        Supprimer définitivement mon compte
    </button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-6 sm:p-8 space-y-5">
            @csrf
            @method('delete')

            <div>
                <h2 class="text-xl font-bold">Confirmer la suppression du compte</h2>
                <p class="text-sm text-slate-600 mt-1">
                    Cette action est irréversible. Toutes vos places, réservations et préférences seront supprimées.
                </p>
            </div>

            @if($errors->userDeletion->get('account'))
                <div class="booking-error !m-0" role="alert">
                    {{ $errors->userDeletion->first('account') }}
                </div>
            @endif

            <div>
                <label for="password" class="block text-sm font-semibold mb-1.5">
                    Entrez votre mot de passe pour confirmer
                </label>
                <input
                    id="password"
                    name="password"
                    type="password"
                    placeholder="Votre mot de passe"
                    class="ep-input"
                />
                @if($errors->userDeletion->get('password'))
                    <p class="mt-1.5 text-sm text-rose-700">{{ $errors->userDeletion->first('password') }}</p>
                @endif
            </div>

            <div class="flex flex-wrap items-center justify-end gap-3 pt-4 border-t border-[#dce3ed]">
                <button type="button" x-on:click="$dispatch('close')" class="booking-secondary">
                    Annuler
                </button>
                <button type="submit" class="booking-danger">
                    Supprimer définitivement
                </button>
            </div>
        </form>
    </x-modal>
</section>
