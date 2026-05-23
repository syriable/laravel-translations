<h1>Welcome to our site</h1>

<p>{{ __('messages.intro') }}</p>

<input type="text" placeholder="Enter your name">

<button>{{ __('buttons.save') }}</button>

<span>Already translated: {{ $value }}</span>

{{-- Hidden comment text --}}

@php
    $code = 'a code string';
@endphp
