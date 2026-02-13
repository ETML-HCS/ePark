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

    <form method="post" action="{{ route('profile.secret-groups.store') }}" class="space-y-5">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div>
                <label for="secret_group_name" class="text-sm font-bold text-gray-900 mb-2 block">Nom du groupe</label>
                <input
                    id="secret_group_name"
                    name="secret_group_name"
                    type="text"
                    value="{{ old('secret_group_name') }}"
                    placeholder="Ex: ETML/CFPV"
                    class="w-full px-4 py-3 bg-gray-50 border-2 border-gray-200 rounded-xl focus:ring-0 focus:border-indigo-500 hover:border-gray-300 transition-all font-medium text-gray-900">
            </div>
            <div>
                <label for="secret_group_code" class="text-sm font-bold text-gray-900 mb-2 block">Code du groupe</label>
                <input
                    id="secret_group_code"
                    name="secret_group_code"
                    type="text"
                    value="{{ old('secret_group_code') }}"
                    placeholder="Ex: etml3865"
                    class="w-full px-4 py-3 bg-gray-50 border-2 border-gray-200 rounded-xl focus:ring-0 focus:border-indigo-500 hover:border-gray-300 transition-all font-medium text-gray-900">
            </div>
        </div>

        @if($errors->secretGroup->get('secret_group_name') || $errors->secretGroup->get('secret_group_code'))
            <p class="text-sm text-red-600">{{ $errors->secretGroup->first('secret_group_name') ?: $errors->secretGroup->first('secret_group_code') }}</p>
        @endif

        @if (session('status') === 'secret-group-added')
            <p class="text-sm text-green-700 font-medium">Groupe validé et ajouté avec succès.</p>
        @elseif (session('status') === 'secret-group-exists')
            <p class="text-sm text-amber-700 font-medium">Ce groupe est déjà présent dans votre liste.</p>
        @elseif (session('status') === 'secret-group-removed')
            <p class="text-sm text-green-700 font-medium">Groupe retiré de votre liste.</p>
        @endif

        <div class="flex items-center gap-3">
            <button type="submit" class="inline-flex items-center gap-2 px-5 py-3 bg-indigo-600 text-white rounded-xl font-bold hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-200">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Ajouter
            </button>
            <span class="text-xs text-gray-500">Vérification immédiate du couple nom + code</span>
        </div>
    </form>

    <div class="mt-6 border-t border-gray-100 pt-4">
        <h4 class="text-sm font-black text-gray-900 uppercase tracking-wide mb-3">Liste des groupes secrets</h4>

        @if(empty($savedGroups))
            <p class="text-sm text-gray-500">Aucun groupe secret ajouté pour le moment.</p>
        @else
            <div class="space-y-2">
                @foreach($savedGroups as $entry)
                    <div class="flex items-center justify-between rounded-xl border border-gray-100 bg-gray-50 px-3 py-2">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-gray-900 truncate">{{ $entry['name'] !== '' ? $entry['name'] : 'Groupe sans nom' }}</p>
                            <p class="text-xs text-gray-500">Code: {{ $maskedCode($entry['code']) }}</p>
                        </div>
                        <form method="post" action="{{ route('profile.secret-groups.destroy') }}" onsubmit="return confirm('Retirer ce groupe secret ?');">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="secret_group_code_remove" value="{{ $entry['code'] }}">
                            <button type="submit" class="px-3 py-1.5 rounded-lg bg-red-50 text-red-600 text-xs font-bold hover:bg-red-100 transition-all">
                                Retirer
                            </button>
                        </form>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</section>
