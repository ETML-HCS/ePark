<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center px-4 py-2 bg-yellow-500 border border-transparent rounded-xl font-semibold text-sm text-white shadow-sm hover:bg-yellow-600 active:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-yellow-400 focus:ring-offset-2 disabled:opacity-60 transition-colors duration-150']) }}>
    {{ $slot }}
</button>
