<x-guest-layout>
    <form method="POST" action="{{ route('register') }}">
        @csrf

        <!-- Name -->
        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Email Address -->
        <div class="mt-4">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Site favori -->
        <div class="mt-4">
            <x-input-label for="favorite_site_id" :value="__('Site favori')" />
            <select id="favorite_site_id" name="favorite_site_id" class="block mt-1 w-full border-2 border-gray-200 bg-gray-50 focus:border-indigo-500 focus:ring-0 rounded-xl text-sm font-medium text-gray-900" required>
                <option value="">Sélectionner un site</option>
                @foreach($sites as $site)
                    <option value="{{ $site->id }}" {{ old('favorite_site_id') == $site->id ? 'selected' : '' }}>
                        {{ $site->nom }} — {{ $site->adresse }}
                    </option>
                @endforeach
                <option value="other" {{ old('favorite_site_id') == 'other' ? 'selected' : '' }}>Autre (je créerai mon site)</option>
            </select>
            <p class="mt-1 text-xs text-gray-500">Si tu choisis “Autre”, tu créeras ton site à la première connexion.</p>
            <x-input-error :messages="$errors->get('favorite_site_id')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />

            <x-text-input id="password_confirmation" class="block mt-1 w-full"
                            type="password"
                            name="password_confirmation" required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-xl focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('login') }}">
                {{ __('Already registered?') }}
            </a>

            <x-primary-button class="ms-4">
                {{ __('Register') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
