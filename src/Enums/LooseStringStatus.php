<?php

namespace Syriable\Translations\Enums;

enum LooseStringStatus: string
{
    case Pending = 'pending';
    case Converted = 'converted';
    case Ignored = 'ignored';
    case Resolved = 'resolved';
}
