<?php

namespace Syriable\Translations\Policies;

use Syriable\Translations\Contracts\HasTranslationRole;
use Syriable\Translations\Enums\MemberRole;
use Syriable\Translations\Models\Message;

/**
 * Not registered automatically - the package has no auth layer. Register it
 * yourself against your configured member model, e.g. in AppServiceProvider:
 *
 *   Gate::policy(Message::class, MessagePolicy::class);
 */
class MessagePolicy
{
    public function translate(mixed $member, Message $message): bool
    {
        return $this->roleOf($member)?->canTranslate() ?? false;
    }

    public function review(mixed $member, Message $message): bool
    {
        return $this->roleOf($member)?->canReview() ?? false;
    }

    public function manage(mixed $member, Message $message): bool
    {
        return $this->roleOf($member)?->canManage() ?? false;
    }

    protected function roleOf(mixed $member): ?MemberRole
    {
        return $member instanceof HasTranslationRole ? $member->translationRole() : null;
    }
}
