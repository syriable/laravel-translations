<?php

// This is a fixture file scanned as text by the extractor. It is never executed.

echo __('messages.welcome');

$greeting = trans('messages.greeting', ['name' => $name]);

trans_choice('messages.apples', 5);

$dynamic = __($variable);

$concatenated = __('messages.'.$suffix);
