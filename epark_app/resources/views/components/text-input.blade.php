@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-2 border-gray-200 bg-gray-50 rounded-xl focus:border-indigo-500 focus:ring-0 text-sm font-medium text-gray-900 hover:border-gray-300 transition-all']) }}>
