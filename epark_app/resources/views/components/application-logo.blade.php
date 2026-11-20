@props(['full' => false])

@if($full)
    <img
        src="{{ asset('logo/athys-epark-logo-positive.svg') }}"
        alt="ePark by athys"
        {{ $attributes->merge(['class' => 'h-9 w-auto']) }}
    />
@else
    <svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" {{ $attributes->merge(['class' => 'h-8 w-8']) }} role="img" aria-label="ePark by athys"><defs><linearGradient id="epark-blade-fv-epark" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#F7BF26"/><stop offset="1" stop-color="#F7941D"/></linearGradient></defs><rect width="64" height="64" rx="15" fill="url(#epark-blade-fv-epark)"/><g transform="translate(32 32) scale(1.8) translate(-12 -12)" stroke-linecap="round" stroke-linejoin="round"><rect x="4.25" y="2.75" width="15.5" height="18.5" rx="3" fill="none" stroke="#0A1128" stroke-width="2.6"/><path d="M10.6 9.6V5.2H12.7A1.5 1.5 0 0 1 12.7 8.2H10.6" fill="none" stroke="#0A1128" stroke-width="2.6"/><path d="M6 18.4A6 6 0 0 1 18 18.4H15A3 3 0 0 0 9 18.4Z" fill="#0A1128" fill-opacity="0.2"/><path d="M6 18.4A6 6 0 0 1 18 18.4H15A3 3 0 0 0 9 18.4Z" fill="none" stroke="#0A1128" stroke-width="1.1"/><circle cx="8.10" cy="16.15" r="0.68" fill="#0A1128"/><circle cx="9.75" cy="14.50" r="0.68" fill="#0A1128"/><circle cx="12.00" cy="13.90" r="0.68" fill="#0A1128"/><circle cx="14.25" cy="14.50" r="0.68" fill="#0A1128"/><circle cx="15.90" cy="16.15" r="0.68" fill="#0A1128"/><path d="M12 15.70L13.15 18.20H10.85Z" fill="#0A1128"/></g></svg>
@endif
