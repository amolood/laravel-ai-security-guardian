<?php

namespace Abdalmolood\AiSecurityGuardian\Enums;

enum FixMode: string
{
    case SAFE_AUTO_FIX = 'safe_auto_fix';
    case HUMAN_REVIEW_REQUIRED = 'human_review_required';
    case NEVER_AUTO_FIX = 'never_auto_fix';
}
