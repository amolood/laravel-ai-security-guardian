<?php

namespace Abdalmolood\AiSecurityGuardian\Scanners\Ast\Visitors;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use Abdalmolood\AiSecurityGuardian\DTO\Finding;
use Abdalmolood\AiSecurityGuardian\Enums\Severity;

class WebhookVisitor extends BaseVisitor
{
    public function enterNode(Node $node)
    {
        if ($node instanceof Class_ && $node->name instanceof Node\Identifier) {
            $className = strtolower($node->name->toString());
            
            if (str_contains($className, 'webhook')) {
                // Check if hash_equals is used anywhere in the class
                $hasSignatureCheck = $this->hasHashEqualsCheck($node->stmts);

                if (!$hasSignatureCheck) {
                    $this->addFinding(new Finding(
                        title: 'Webhook Missing Signature Verification',
                        description: "The class `{$node->name->toString()}` appears to handle webhooks but does not use `hash_equals()` for signature verification.",
                        severity: Severity::HIGH,
                        category: 'webhook_security',
                        affectedFile: $this->currentFile,
                        affectedLine: $node->getStartLine(),
                        recommendation: 'Ensure webhook payloads are verified using `hash_equals` to compare the calculated HMAC signature with the provided header signature.',
                        safeAutoFixAllowed: false,
                    ));
                }
            }
        }

        return null;
    }

    protected function hasHashEqualsCheck(array $stmts): bool
    {
        $nodeFinder = new \PhpParser\NodeFinder();
        $checks = $nodeFinder->find($stmts, function(Node $n) {
            if ($n instanceof FuncCall && $n->name instanceof Node\Name) {
                return $n->name->toString() === 'hash_equals';
            }
            // Also accept checking via a package method like $webhook->verify()
            if ($n instanceof MethodCall && $n->name instanceof Node\Identifier) {
                return in_array($n->name->toString(), ['verifySignature', 'verify']);
            }
            return false;
        });

        return count($checks) > 0;
    }
}
