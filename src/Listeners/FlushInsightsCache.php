<?php

namespace Syriable\Translations\Listeners;

use Syriable\Translations\Analytics\Insights;

class FlushInsightsCache
{
    public function __construct(
        private readonly Insights $insights,
    ) {}

    public function handle(object $event): void
    {
        $this->insights->flush();
    }
}
