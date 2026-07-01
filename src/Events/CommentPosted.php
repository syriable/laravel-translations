<?php

namespace Syriable\Translations\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Syriable\Translations\Models\Comment;

class CommentPosted
{
    use Dispatchable;

    public function __construct(
        public Comment $comment,
    ) {}
}
