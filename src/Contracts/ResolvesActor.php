<?php

namespace Syriable\Translations\Contracts;

interface ResolvesActor
{
    /**
     * Identify whoever is behind the current action (manual edit, AI trigger,
     * review, rollback, ...) when the caller didn't explicitly pass one.
     */
    public function resolve(): ?string;
}
