<?php

namespace Syriable\Translations\Contracts;

use Syriable\Translations\Enums\MemberRole;

interface HasTranslationRole
{
    public function translationRole(): ?MemberRole;
}
