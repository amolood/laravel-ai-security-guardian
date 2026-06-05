<?php

namespace Abdalmolood\AiSecurityGuardian\Scanners\Ast;

use Illuminate\Support\Collection;
use Abdalmolood\AiSecurityGuardian\Scanners\Ast\Visitors\LogVisitor;

class AstLogScanner extends AbstractAstScanner
{
    public function getName(): string
    {
        return 'AST Log Security Scanner';
    }

    public function scan(): Collection
    {
        $visitor = new LogVisitor();
        return $this->parseFiles(app_path(), $visitor);
    }
}
