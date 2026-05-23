<?php

declare(strict_types=1);

namespace Syriable\Translations\Domain\Enums;

/**
 * Lifecycle state of a detected hardcoded string.
 */
enum HardcodedStatus: string
{
    case Pending = 'pending';
    case Ignored = 'ignored';
    case Converted = 'converted';
}
