<?php

namespace Syriable\Translations\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Auth;
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

    /**
     * Resolve the Member matching the currently authenticated app user (by email).
     *
     * Use this instead of auth()->id() when passing a "member id" into this
     * package (e.g. ReviewFlow::reject(), Message::comment()): the host
     * app's own user id is rarely the same value as this package's Member
     * ulid primary key.
     */
    public static function current(): ?self
    {
        $user = Auth::user();

        if ($user === null || $user->email === null) {
            return null;
        }

        return static::query()->where('email', $user->email)->first();
    }
}
