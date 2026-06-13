<?php

namespace Syriable\Translations\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Syriable\Translations\Models\Phrase;

class PhraseCreated
{
    use Dispatchable;

    public function __construct(
        public Phrase $phrase,
    ) {}
}
