<h1>{{ __('messages.welcome') }}</h1>

<p>@lang('messages.tagline')</p>

{{-- {{ __('ignored.comment') }} --}}

<span>{{ trans_choice('messages.apples', $count) }}</span>

@php
    $page = __('messages.page_title');
@endphp
