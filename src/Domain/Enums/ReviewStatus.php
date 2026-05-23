<?php

declare(strict_types=1);

namespace Syriable\Translations\Domain\Enums;

/**
 * The review state of a translation in the approval workflow.
 */
enum ReviewStatus: string
{
    case NeedsReview = 'needs_review';
    case Approved = 'approved';
    case Rejected = 'rejected';
}
