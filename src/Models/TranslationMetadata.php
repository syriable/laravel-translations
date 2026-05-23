<?php

declare(strict_types=1);

namespace Syriable\Translations\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Base model for the package's metadata tables. Resolves its connection from
 * config so all metadata can live on a dedicated database if desired, while the
 * canonical translation values stay in lang files.
 */
abstract class TranslationMetadata extends Model
{
    protected $guarded = [];

    public function getConnectionName(): ?string
    {
        return config('translations.metadata.connection') ?: parent::getConnectionName();
    }
}
