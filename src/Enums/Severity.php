<?php

namespace Abdalmolood\AiSecurityGuardian\Enums;

enum Severity: string
{
    case CRITICAL = 'critical';
    case HIGH = 'high';
    case MEDIUM = 'medium';
    case LOW = 'low';
    case INFO = 'info';
}
