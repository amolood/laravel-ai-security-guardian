<?php

namespace Abdalmolood\AiSecurityGuardian\Scanners\Ast\Visitors;

use PhpParser\Node;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use Abdalmolood\AiSecurityGuardian\DTO\Finding;
use Abdalmolood\AiSecurityGuardian\Enums\Severity;

class TenantIsolationVisitor extends BaseVisitor
{
    public function enterNode(Node $node)
    {
        if ($node instanceof PropertyFetch && $node->var instanceof Variable) {
            if (is_string($node->var->name) && in_array($node->var->name, ['request', 'user'])) {
                if ($node->name instanceof Node\Identifier && in_array($node->name->toString(), ['tenant_id', 'company_id'])) {
                    $this->addFinding(new Finding(
                        title: 'Direct Tenant ID Access',
                        description: "Directly accessing `\$request->tenant_id` or `\$user->tenant_id` detected. Tenant contexts should be isolated automatically via global scopes or middleware, rather than trusted from user input or checked manually.",
                        severity: Severity::MEDIUM,
                        category: 'tenant_isolation',
                        affectedFile: $this->currentFile,
                        affectedLine: $node->getStartLine(),
                        recommendation: 'Ensure you are using route model binding with global tenant scopes, rather than relying on direct ID comparisons or assignment.',
                        safeAutoFixAllowed: false,
                    ));
                }
            }
        }

        return null;
    }
}
