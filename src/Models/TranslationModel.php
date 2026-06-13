<?php

namespace Syriable\Translations\Models;

use Illuminate\Database\Eloquent\Model;

abstract class TranslationModel extends Model
{
    protected string $table_;

    public function getTable(): string
    {
        return config('translations.database.prefix', 'tx_').$this->table_;
    }

    public function getConnectionName(): ?string
    {
        return config('translations.database.connection') ?? $this->connection;
    }
}
