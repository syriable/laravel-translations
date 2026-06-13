<?php

namespace Syriable\Translations\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Syriable\Translations\Models\Locale;

class LocaleAdded
{
    use Dispatchable;

    public function __construct(
        public Locale $locale,
    ) {}
}
