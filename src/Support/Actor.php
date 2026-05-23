<?php

declare(strict_types=1);

namespace Syriable\Translations\Support;

use Closure;
use Throwable;

/**
 * Resolves the identifier of whoever is performing a translation change, used
 * to attribute revisions, comments and activity. Defaults to the authenticated
 * user's id, and falls back to null (e.g. CLI) when no user is available.
 * Applications can override resolution via {@see resolveUsing()}.
 */
final class Actor
{
    /**
     * @var (Closure(): (int|string|null))|null
     */
    private static ?Closure $resolver = null;

    /**
     * @param  (Closure(): (int|string|null))|null  $resolver
     */
    public static function resolveUsing(?Closure $resolver): void
    {
        self::$resolver = $resolver;
    }

    public static function current(): ?string
    {
        $id = self::$resolver !== null ? (self::$resolver)() : self::authId();

        return $id === null ? null : (string) $id;
    }

    private static function authId(): int|string|null
    {
        try {
            return auth()->id();
        } catch (Throwable) {
            return null;
        }
    }
}
