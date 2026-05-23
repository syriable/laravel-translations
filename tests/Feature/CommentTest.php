<?php

declare(strict_types=1);

use Syriable\Translations\Collaboration\CommentService;
use Syriable\Translations\Models\ActivityLog;
use Syriable\Translations\Support\Actor;

beforeEach(function () {
    $this->comments = app(CommentService::class);
});

afterEach(function () {
    Actor::resolveUsing(null);
});

it('posts a comment attributed to the current actor', function () {
    Actor::resolveUsing(fn (): string => 'translator-3');

    $comment = $this->comments->post('fr', 'messages.welcome', 'Should this be formal?');

    expect($comment->body)->toBe('Should this be formal?')
        ->and($comment->user_id)->toBe('translator-3')
        ->and($comment->type)->toBe('comment');
});

it('returns the thread for a translation oldest first', function () {
    $this->comments->post('fr', 'messages.welcome', 'First');
    $this->comments->post('fr', 'messages.welcome', 'Second');
    $this->comments->post('fr', 'messages.other', 'Different key');

    $thread = $this->comments->forKey('fr', 'messages.welcome');

    expect($thread)->toHaveCount(2)
        ->and($thread->first()->body)->toBe('First')
        ->and($thread->last()->body)->toBe('Second');
});

it('logs comment activity', function () {
    $this->comments->post('fr', 'messages.welcome', 'A note');

    $log = ActivityLog::query()->where('action', 'comment.posted')->sole();

    expect($log->locale)->toBe('fr')
        ->and($log->translation_key)->toBe('messages.welcome');
});
