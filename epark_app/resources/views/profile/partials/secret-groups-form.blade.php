<section>
    @php
        $savedGroups = $user->secretGroupEntries();
        $maskedCode = static function (string $code): string {
            $visible = 3;
            $length = mb_strlen($code);
            if ($length <= $visible) {
                return str_repeat('•', $length);
            }

            return str_repeat('•', $length - $visible).mb_substr($code, -$visible);
        };
    @endphp

    <form method="post" action="{{ route('profile.secret-groups.store') }}" class="space-y-4">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label for="secret_group_name" class="block text-sm font-semibold mb-1.5">Nom du groupe</label>
                <input
                    id="secret_group_name"
                    name="secret_group_name"
                    type="text"
                    value="{{ old('secret_group_name') }}"
                    placeholder="Ex. : ETML/CFPV"
                    class="ep-input"
                    required>
            </div>
            <div>
                <label for="secret_group_code" class="block text-sm font-semibold mb-1.5">Code du groupe</label>
                <input
                    id="secret_group_code"
                    name="secret_group_code"
                    type="text"
                    value="{{ old('secret_group_code') }}"
                    placeholder="Ex. : etml3865"
                    autocomplete="off"
                    class="ep-input"
                    required>
            </div>
        </div>

        @if($errors->secretGroup->get('secret_group_name') || $errors->secretGroup->get('secret_group_code'))
            <p class="booking-error !m-0" role="alert">
                {{ $errors->secretGroup->first('secret_group_name') ?: $errors->secretGroup->first('secret_group_code') }}
            </p>
        @endif

        @if (session('status') === 'secret-group-added')
            <p class="text-sm font-semibold text-[#166534]" role="status">Groupe validé et ajouté avec succès.</p>
        @elseif (session('status') === 'secret-group-exists')
            <p class="text-sm font-semibold text-amber-800" role="status">Ce groupe est déjà présent dans votre liste.</p>
        @elseif (session('status') === 'secret-group-removed')
            <p class="text-sm font-semibold text-[#166534]" role="status">Groupe retiré de votre liste.</p>
        @endif

        <div class="flex flex-wrap items-center gap-3">
            <button type="submit" class="booking-primary">Ajouter le groupe</button>
            <span class="text-xs text-slate-500">Vérification immédiate du nom et du code</span>
        </div>
    </form>

    <div class="mt-6 pt-6 border-t border-[#dce3ed]">
        <h3 class="text-sm font-bold mb-3">Mes groupes enregistrés</h3>

        @if(empty($savedGroups))
            <p class="text-sm text-slate-600">Aucun groupe privé enregistré pour le moment.</p>
        @else
            <div class="space-y-2.5">
                @foreach($savedGroups as $entry)
                    <div class="flex items-center justify-between gap-3 rounded-xl border border-[#dce3ed] bg-[#f8fafc] px-4 py-3">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold truncate">{{ $entry['name'] !== '' ? $entry['name'] : 'Groupe sans nom' }}</p>
                            <p class="text-xs text-slate-500">Code : {{ $maskedCode($entry['code']) }}</p>
                        </div>
                        <form method="post" action="{{ route('profile.secret-groups.destroy') }}" onsubmit="return confirm('Retirer ce groupe privé ?');">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="secret_group_code_remove" value="{{ $entry['code'] }}">
                            <button type="submit" class="booking-secondary !min-h-[36px] !py-1.5 !px-3 !text-xs !text-rose-700 hover:!bg-rose-50">
                                Retirer
                            </button>
                        </form>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</section>
