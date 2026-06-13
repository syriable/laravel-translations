<?php

namespace Syriable\Translations\Support;

use Illuminate\Database\Eloquent\Model;
use Syriable\Translations\Models\Activity;

class ActivityRecorder
{
    public function log(string $action, ?Model $subject = null, array $meta = [], ?string $memberId = null): Activity
    {
        return Activity::query()->create([
            'member_id' => $memberId,
            'action' => $action,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'meta' => $meta,
        ]);
    }
}
