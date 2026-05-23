<?php

declare(strict_types=1);

namespace Syriable\Translations\Console\Commands;

use Illuminate\Console\Command;
use Syriable\Translations\Console\Concerns\InteractsWithCatalog;
use Syriable\Translations\Storage\StorageManager;

final class LocalesCommand extends Command
{
    use InteractsWithCatalog;

    protected $signature = 'translations:locales';

    protected $description = 'List discovered locales and their key counts';

    public function handle(StorageManager $storage): int
    {
        $catalog = $this->catalog($storage);
        $source = $this->sourceLocale();

        $rows = [];

        foreach ($catalog->all() as $code => $localeCatalog) {
            $rows[] = [
                $code,
                $code === $source ? 'source' : '',
                $localeCatalog->count(),
                $localeCatalog->translatedCount(),
            ];
        }

        if ($rows === []) {
            $this->warn('No locales found.');

            return self::SUCCESS;
        }

        $this->table(['Locale', 'Role', 'Keys', 'Translated'], $rows);

        return self::SUCCESS;
    }
}
