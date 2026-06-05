<?php

namespace Abdalmolood\AiSecurityGuardian\Scanners\Ast\Visitors;

use PhpParser\Node;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\MethodCall;
use Abdalmolood\AiSecurityGuardian\DTO\Finding;
use Abdalmolood\AiSecurityGuardian\Enums\Severity;

class RaceConditionVisitor extends BaseVisitor
{
    protected array $criticalActions = [
        'payment', 'checkout', 'inventory', 'stock', 'deduct', 'charge', 'subscribe'
    ];

    public function enterNode(Node $node)
    {
        // Find methods that look like critical financial/inventory actions
        if ($node instanceof ClassMethod && $node->name instanceof Node\Identifier) {
            $methodName = strtolower($node->name->toString());
            
            $isCritical = false;
            foreach ($this->criticalActions as $action) {
                if (str_contains($methodName, $action)) {
                    $isCritical = true;
                    break;
                }
            }

            if ($isCritical) {
                // Check if the method body contains DB::transaction or lockForUpdate
                $hasTransaction = $this->hasTransactionOrLock($node->getStmts() ?? []);

                if (!$hasTransaction) {
                    $this->addFinding(new Finding(
                        title: 'Potential Race Condition in Critical Action',
                        description: "The method `{$methodName}` appears to handle critical logic but does not use `DB::transaction()` or `->lockForUpdate()`. This could lead to race conditions (e.g., double spending or overselling stock).",
                        severity: Severity::HIGH,
                        category: 'race_condition',
                        affectedFile: $this->currentFile,
                        affectedLine: $node->getStartLine(),
                        recommendation: 'Wrap the critical updates in a database transaction and use pessimistic locking (`lockForUpdate`).',
                        safeAutoFixAllowed: false,
                    ));
                }
            }
        }

        return null;
    }

    protected function hasTransactionOrLock(array $stmts): bool
    {
        $hasLock = false;
        
        $nodeFinder = new \PhpParser\NodeFinder();
        $transactions = $nodeFinder->find($stmts, function(Node $n) {
            if ($n instanceof StaticCall && $n->class instanceof Node\Name && $n->name instanceof Node\Identifier) {
                return $n->class->toString() === 'DB' && $n->name->toString() === 'transaction';
            }
            return false;
        });

        $locks = $nodeFinder->find($stmts, function(Node $n) {
            if ($n instanceof MethodCall && $n->name instanceof Node\Identifier) {
                return in_array($n->name->toString(), ['lockForUpdate', 'sharedLock']);
            }
            return false;
        });

        return count($transactions) > 0 || count($locks) > 0;
    }
}
