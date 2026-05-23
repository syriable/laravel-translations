<?php

declare(strict_types=1);

namespace Syriable\Translations\Collaboration;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Collection;
use Syriable\Translations\Events\CommentPosted;
use Syriable\Translations\Models\TranslationComment;
use Syriable\Translations\Support\Actor;

/**
 * Manages discussion notes attached to translations, letting translators and
 * reviewers collaborate per locale + key.
 */
final class CommentService
{
    public function __construct(private readonly Dispatcher $events) {}

    public function enabled(): bool
    {
        return config('translations.metadata.enabled', true) === true;
    }

    public function post(string $locale, string $key, string $body, string $type = 'comment', ?string $author = null): TranslationComment
    {
        $comment = TranslationComment::query()->create([
            'locale' => $locale,
            'translation_key' => $key,
            'key_hash' => TranslationComment::hashKey($key),
            'user_id' => $author ?? Actor::current(),
            'body' => $body,
            'type' => $type,
        ]);

        $this->events->dispatch(new CommentPosted($locale, $key, $comment->user_id));

        return $comment;
    }

    /**
     * The comment thread for a translation, oldest first.
     *
     * @return Collection<int, TranslationComment>
     */
    public function forKey(string $locale, string $key): Collection
    {
        return TranslationComment::query()
            ->forKey($locale, $key)
            ->orderBy('created_at')
            ->get();
    }
}
