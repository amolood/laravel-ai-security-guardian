<?php

namespace Abdalmolood\AiSecurityGuardian\Scanners\Ast;

use Illuminate\Support\Collection;
use Abdalmolood\AiSecurityGuardian\Scanners\Ast\Visitors\CodeSecurityVisitor;

class AstCodeScanner extends AbstractAstScanner
{
    public function getName(): string
    {
        return 'AST Code Analysis Scanner';
    }

    public function scan(): Collection
    {
        $visitor = new CodeSecurityVisitor();
        return $this->parseFiles(app_path(), $visitor);
    }
}
