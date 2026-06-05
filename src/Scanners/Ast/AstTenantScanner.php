<?php

namespace Abdalmolood\AiSecurityGuardian\Scanners\Ast;

use Illuminate\Support\Collection;
use Abdalmolood\AiSecurityGuardian\Scanners\Ast\Visitors\TenantIsolationVisitor;

class AstTenantScanner extends AbstractAstScanner
{
    public function getName(): string
    {
        return 'AST Tenant Isolation Scanner';
    }

    public function scan(): Collection
    {
        $visitor = new TenantIsolationVisitor();
        return $this->parseFiles(app_path(), $visitor);
    }
}
