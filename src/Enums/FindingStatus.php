<?php

namespace Abdalmolood\AiSecurityGuardian\Enums;

enum FindingStatus: string
{
    case OPEN = 'open';
    case IN_REVIEW = 'in_review';
    case FIXED = 'fixed';
    case RESOLVED = 'resolved';
    case ACCEPTED_RISK = 'accepted_risk';
    case FALSE_POSITIVE = 'false_positive';
    case IGNORED = 'ignored';
}
