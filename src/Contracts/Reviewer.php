<?php

namespace Syriable\Translations\Contracts;

use Syriable\Translations\Support\ReviewRequest;
use Syriable\Translations\Support\ReviewResult;

interface Reviewer
{
    public function review(ReviewRequest $request): ReviewResult;
}
