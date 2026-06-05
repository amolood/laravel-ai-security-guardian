<?php

namespace Abdalmolood\AiSecurityGuardian\Scanners\Ast;

use Illuminate\Support\Collection;
use Abdalmolood\AiSecurityGuardian\Scanners\Ast\Visitors\WebhookVisitor;

class AstWebhookScanner extends AbstractAstScanner
{
    public function getName(): string
    {
        return 'AST Webhook Security Scanner';
    }

    public function scan(): Collection
    {
        $visitor = new WebhookVisitor();
        return $this->parseFiles(app_path('Http/Controllers'), $visitor);
    }
}
