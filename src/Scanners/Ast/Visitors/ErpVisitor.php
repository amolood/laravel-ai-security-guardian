<?php

namespace Abdalmolood\AiSecurityGuardian\Scanners\Ast\Visitors;

use PhpParser\Node;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\NodeFinder;
use Abdalmolood\AiSecurityGuardian\DTO\Finding;
use Abdalmolood\AiSecurityGuardian\Enums\Severity;

class ErpVisitor extends BaseVisitor
{
    public function enterNode(Node $node)
    {
        if ($node instanceof ClassMethod && $node->name instanceof Node\Identifier) {
            $methodName = strtolower($node->name->toString());
            
            if ($methodName === 'update' || $methodName === 'edit') {
                $nodeFinder = new NodeFinder();
                
                // Check if they call save() or update()
                $saves = $nodeFinder->find($node->stmts ?? [], function(Node $n) {
                    if ($n instanceof MethodCall && $n->name instanceof Node\Identifier) {
                        return in_array($n->name->toString(), ['save', 'update']);
                    }
                    return false;
                });

                if (count($saves) > 0) {
                    // Check if they verify status (e.g., $model->status === 'approved')
                    $statusChecks = $nodeFinder->find($node->stmts ?? [], function(Node $n) {
                        if ($n instanceof PropertyFetch && $n->name instanceof Node\Identifier) {
                            return str_contains(strtolower($n->name->toString()), 'status');
                        }
                        return false;
                    });

                    if (count($statusChecks) === 0) {
                        // This is a heuristic: updating without checking status in an ERP is dangerous
                        $this->addFinding(new Finding(
                            title: 'Unverified Modification of ERP Entity',
                            description: "An `update` method was found that does not verify the entity's `status` before saving. In ERP systems, approved invoices or posted journal entries should be immutable or require strict un-posting workflows.",
                            severity: Severity::MEDIUM,
                            category: 'business_logic',
                            affectedFile: $this->currentFile,
                            affectedLine: $node->getStartLine(),
                            recommendation: 'Add a check (e.g., `if ($model->status === "approved") { abort(403); }`) to prevent tampering with finalized records.',
                            safeAutoFixAllowed: false,
                        ));
                    }
                }
            }
        }

        return null;
    }
}
