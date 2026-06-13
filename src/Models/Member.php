<?php

namespace Syriable\Translations\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Syriable\Translations\Enums\MemberRole;

class Member extends TranslationModel
{
    use HasUlids;

    protected string $table_ = 'members';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'role' => MemberRole::class,
            'enabled' => 'boolean',
            'last_seen_at' => 'datetime',
        ];
    }

    public function locales(): BelongsToMany
    {
        return $this->belongsToMany(Locale::class, config('translations.database.prefix', 'tx_').'member_locale');
    }
}
