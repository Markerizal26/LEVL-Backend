@component('mail::message')
# Export Data Pengguna

Halo,

Export data pengguna Anda telah selesai dan terlampir dalam email ini.

**File**: {{ $fileName }}

Terima kasih,<br>
Tim {{ config('app.name') }}
@endcomponent
