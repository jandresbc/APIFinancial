@component('mail::message')
    # {{ $email }}

    # {{$matter}}

    {{$message}}

    @component('mail::button', ['url' => 'https://creditek.com.co/'])
        Go to CrediTek.com.co
    @endcomponent

    Thanks {{ config('app.name') }}
@endcomponent
