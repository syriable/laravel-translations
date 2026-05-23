<?php

declare(strict_types=1);

namespace Syriable\Translations\Domain\Enums;

enum KeyType: string
{
    case Php = 'php';
    case Json = 'json';

    public function extension(): string
    {
        return $this->value;
    }
}
