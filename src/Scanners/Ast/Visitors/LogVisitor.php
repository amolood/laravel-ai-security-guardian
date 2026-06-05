<?php

namespace Abdalmolood\AiSecurityGuardian\Scanners\Ast\Visitors;

use PhpParser\Node;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use Abdalmolood\AiSecurityGuardian\DTO\Finding;
use Abdalmolood\AiSecurityGuardian\Enums\Severity;

class LogVisitor extends BaseVisitor
{
    protected array $sensitiveKeys = [
        'password', 'token', 'secret', 'key', 'credential', 'auth'
    ];

    public function enterNode(Node $node)
    {
        if ($node instanceof StaticCall && $node->class instanceof Node\Name && $node->class->toString() === 'Log') {
            // Very basic AST traversal to see if $request->password etc is inside Log::info()
            $nodeFinder = new \PhpParser\NodeFinder();
            $propertyFetches = $nodeFinder->findInstanceOf($node->args, PropertyFetch::class);

            foreach ($propertyFetches as $fetch) {
                if ($fetch->var instanceof Variable && is_string($fetch->var->name) && $fetch->var->name === 'request') {
                    if ($fetch->name instanceof Node\Identifier) {
                        $propName = strtolower($fetch->name->toString());
                        foreach ($this->sensitiveKeys as $key) {
                            if (str_contains($propName, $key)) {
                                $this->addFinding(new Finding(
                                    title: 'Sensitive Data Logged',
                                    description: "Logging sensitive data (`\$request->{$propName}`) detected. Do not log passwords, tokens, or PII.",
                                    severity: Severity::HIGH,
                                    category: 'sensitive_data_exposure',
                                    affectedFile: $this->currentFile,
                                    affectedLine: $node->getStartLine(),
                                    recommendation: 'Remove this logging statement or hash/mask the value before logging.',
                                    safeAutoFixAllowed: false,
                                ));
                                break; // no need to flag multiple times for same property
                            }
                        }
                    }
                }
            }
        }

        return null;
    }
}
