<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <div class="p-2 bg-indigo-100 rounded-xl">
                <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
            </div>
            <h2 class="font-black text-2xl text-gray-900">
                Mentions Légales
            </h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm rounded-2xl border border-gray-100 p-6 space-y-6 text-gray-700">
                <section>
                    <h3 class="text-lg font-semibold text-gray-900">Editeur du site</h3>
                    <p>athys technology</p>
                    <p>Responsable de publication : Helder COSTA LOPES by CFPV</p>
                    <p>Contact : <a class="text-indigo-600 hover:underline" href="mailto:info@epark.athys.ch">info@epark.athys.ch</a></p>
                </section>

                <section>
                    <h3 class="text-lg font-semibold text-gray-900">Hebergement</h3>
                    <p>Hostinger</p>
                </section>

                <section>
                    <h3 class="text-lg font-semibold text-gray-900">Propriete intellectuelle</h3>
                    <p>Le contenu du site (textes, visuels, logos, graphismes) est protege par le droit de la propriete intellectuelle. Toute reproduction est interdite sans autorisation prealable.</p>
                </section>

                <section>
                    <h3 class="text-lg font-semibold text-gray-900">Donnees personnelles</h3>
                    <p>Les donnees collectees sont traitees dans le respect de la legislation applicable. Vous pouvez exercer vos droits d'acces, de rectification et de suppression en ecrivant a l'adresse de contact ci-dessus.</p>
                </section>

                <section>
                    <h3 class="text-lg font-semibold text-gray-900">Responsabilite</h3>
                    <p>ePark s'efforce d'assurer l'exactitude des informations presentes sur le site. En cas d'erreur ou d'omission, merci de nous contacter afin que nous procedions aux corrections necessaires.</p>
                </section>
            </div>
        </div>
    </div>
</x-app-layout>
