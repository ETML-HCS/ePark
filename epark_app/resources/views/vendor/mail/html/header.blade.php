<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
@if (trim($slot) === 'ePark' || trim($slot) === config('app.name'))
<img src="{{ asset('logo/ePark.png') }}" class="logo" alt="ePark" style="height: 56px; width: auto;">
@else
{{ $slot }}
@endif
</a>
</td>
</tr>
