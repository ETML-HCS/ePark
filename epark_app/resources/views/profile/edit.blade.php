<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <div class="p-2 bg-indigo-100 rounded-xl">
                <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
            </div>
            <h2 class="font-black text-2xl text-gray-900">
                Mon Profil
            </h2>
        </div>
    </x-slot>

    <div class="py-8 bg-gradient-to-b from-gray-50 to-white min-h-screen">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            
            <!-- User Avatar Card -->
            <div class="bg-gradient-to-br from-indigo-500 via-purple-500 to-indigo-600 rounded-2xl p-8 text-white shadow-xl">
                <div class="flex items-center gap-6">
                    <div class="w-24 h-24 bg-white/10 backdrop-blur-sm rounded-2xl flex items-center justify-center border-4 border-white/20 shadow-2xl">
                        <span class="text-5xl font-black">{{ substr(auth()->user()->name, 0, 1) }}</span>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-3xl font-black mb-2">{{ auth()->user()->name }}</h3>
                        <p class="text-indigo-100 flex items-center gap-2 mb-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                            {{ auth()->user()->email }}
                        </p>
                        @if(auth()->user()->role)
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-white/20 rounded-lg text-sm font-bold backdrop-blur-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"></path>
                                </svg>
                                {{ ucfirst(auth()->user()->role) }}
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Profile Information -->
            <div class="bg-white shadow-sm border border-gray-100 rounded-2xl overflow-hidden">
                <div class="bg-gradient-to-r from-indigo-50 to-purple-50 p-6 border-b border-gray-100">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-indigo-600 rounded-xl">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-black text-gray-900">Informations du profil</h3>
                            <p class="text-sm text-gray-600">Mettez à jour vos informations personnelles</p>
                        </div>
                    </div>
                </div>
                <div class="p-8">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <!-- Update Password -->
            <div class="bg-white shadow-sm border border-gray-100 rounded-2xl overflow-hidden">
                <div class="bg-gradient-to-r from-purple-50 to-pink-50 p-6 border-b border-gray-100">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-purple-600 rounded-xl">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-black text-gray-900">Modifier le mot de passe</h3>
                            <p class="text-sm text-gray-600">Assurez-vous d'utiliser un mot de passe sécurisé</p>
                        </div>
                    </div>
                </div>
                <div class="p-8">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            <!-- Delete Account -->
            <div class="bg-white shadow-sm border border-red-100 rounded-2xl overflow-hidden">
                <div class="bg-gradient-to-r from-red-50 to-orange-50 p-6 border-b border-red-100">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-red-600 rounded-xl">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-black text-red-900">Supprimer le compte</h3>
                            <p class="text-sm text-red-600">Cette action est irréversible</p>
                        </div>
                    </div>
                </div>
                <div class="p-8">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
