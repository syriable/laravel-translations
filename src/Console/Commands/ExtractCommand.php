<?php

declare(strict_types=1);

namespace Syriable\Translations\Console\Commands;

use Illuminate\Console\Command;
use Syriable\Translations\Console\Concerns\InteractsWithCatalog;
use Syriable\Translations\Domain\ExtractedKey;
use Syriable\Translations\Extraction\Extractor;

final class ExtractCommand extends Command
{
    use InteractsWithCatalog;

    protected $signature = 'translations:extract {--json : Output discovered keys as JSON}';

    protected $description = 'Scan source code and list every translation key in use';

    public function handle(Extractor $extractor): int
    {
        $result = $extractor->extract($this->extractionPaths());

        if ($this->option('json')) {
            $this->line((string) json_encode($result->keyStrings(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        $this->info("Discovered {$result->count()} translation keys.");

        $rows = array_map(static fn (ExtractedKey $key): array => [
            $key->key->value,
            $key->referenceCount(),
            (string) ($key->references[0] ?? '—'),
        ], $result->all());

        if ($rows !== []) {
            $this->table(['Key', 'Uses', 'First reference'], $rows);
        }

        return self::SUCCESS;
    }
}
