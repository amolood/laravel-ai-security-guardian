<?php

namespace Abdalmolood\AiSecurityGuardian\Enums;

enum AiProvider: string
{
    case OPENAI = 'openai';
    case CLAUDE = 'claude';
    case GEMINI = 'gemini';
    case DEEPSEEK = 'deepseek';
    case CUSTOM = 'custom';
}
