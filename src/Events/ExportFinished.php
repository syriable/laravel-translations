<?php

namespace Syriable\Translations\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Syriable\Translations\Support\ExportSummary;

class ExportFinished
{
    use Dispatchable;

    public function __construct(
        public ExportSummary $summary,
    ) {}
}
