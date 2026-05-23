<?php

declare(strict_types=1);

namespace Syriable\Translations\Support;

use Syriable\Translations\Domain\Enums\KeyType;

/**
 * Decides where a flat dotted key belongs on disk.
 *
 * Routing follows Laravel conventions:
 *  - "ns::group.item"  -> vendor PHP group file
 *  - "group.item"      -> PHP group file (when the leading segment is a simple
 *                         identifier)
 *  - anything else      -> JSON file (keys-as-sentences)
 */
final class KeyRouter
{
    public function classify(string $key): RoutedKey
    {
        if (str_contains($key, '::')) {
            [$namespace, $remainder] = explode('::', $key, 2);

            return $this->phpGroup($remainder, $namespace);
        }

        if ($this->looksLikeGroupKey($key)) {
            return $this->phpGroup($key, null);
        }

        return new RoutedKey(KeyType::Json, null, null, $key);
    }

    private function phpGroup(string $path, ?string $namespace): RoutedKey
    {
        if (! str_contains($path, '.')) {
            return new RoutedKey(KeyType::Php, $namespace, $path, $path);
        }

        [$group, $item] = explode('.', $path, 2);

        return new RoutedKey(KeyType::Php, $namespace, $group, $item);
    }

    private function looksLikeGroupKey(string $key): bool
    {
        if (! str_contains($key, '.')) {
            return false;
        }

        $leading = explode('.', $key, 2)[0];

        return preg_match('/^[A-Za-z0-9_-]+$/', $leading) === 1;
    }
}
