<?php

namespace Abdalmolood\AiSecurityGuardian\Scanners\Ast;

use Illuminate\Support\Collection;
use Abdalmolood\AiSecurityGuardian\Scanners\Ast\Visitors\QueueVisitor;

class AstQueueScanner extends AbstractAstScanner
{
    public function getName(): string
    {
        return 'AST Queue Security Scanner';
    }

    public function scan(): Collection
    {
        $visitor = new QueueVisitor();
        return $this->parseFiles(app_path('Jobs'), $visitor);
    }
}
