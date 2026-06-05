<?php

namespace Abdalmolood\AiSecurityGuardian\Scanners\Ast;

use Illuminate\Support\Collection;
use Abdalmolood\AiSecurityGuardian\Scanners\Ast\Visitors\RaceConditionVisitor;

class AstRaceConditionScanner extends AbstractAstScanner
{
    public function getName(): string
    {
        return 'AST Race Condition Scanner';
    }

    public function scan(): Collection
    {
        $visitor = new RaceConditionVisitor();
        return $this->parseFiles(app_path('Http/Controllers'), $visitor);
    }
}
