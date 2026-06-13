<?php

namespace Syriable\Translations\Contracts;

use Syriable\Translations\Support\TranslationRequest;
use Syriable\Translations\Support\TranslationResult;

interface Translator
{
    public function translate(TranslationRequest $request): TranslationResult;
}
