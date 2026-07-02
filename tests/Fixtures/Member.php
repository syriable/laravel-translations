<?php

namespace Syriable\Translations\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Syriable\Translations\Contracts\HasTranslationRole;
use Syriable\Translations\Enums\MemberRole;

class Member extends Model implements HasTranslationRole
{
    protected $table = 'fixture_members';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'role' => MemberRole::class,
        ];
    }

    public function translationRole(): ?MemberRole
    {
        return $this->role;
    }
}
