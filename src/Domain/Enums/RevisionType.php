<?php

declare(strict_types=1);

namespace Syriable\Translations\Domain\Enums;

/**
 * The kind of change a revision records.
 */
enum RevisionType: string
{
    case Created = 'created';
    case Updated = 'updated';
    case Deleted = 'deleted';
}
