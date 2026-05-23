<?php

declare(strict_types=1);

namespace Syriable\Translations\Support;

use Syriable\Translations\Domain\Enums\KeyType;

/**
 * The resolved on-disk placement of a translation key.
 */
final readonly class RoutedKey
{
    public function __construct(
        public KeyType $type,
        public ?string $namespace,
        public ?string $group,
        public string $item,
    ) {}
}
