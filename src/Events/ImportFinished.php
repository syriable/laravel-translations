<?php

namespace Syriable\Translations\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Syriable\Translations\Support\ImportSummary;

class ImportFinished
{
    use Dispatchable;

    public function __construct(
        public ImportSummary $summary,
    ) {}
}
