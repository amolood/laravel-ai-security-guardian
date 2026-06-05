<?php

namespace Abdalmolood\AiSecurityGuardian\Scanners\Ast\Visitors;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use Abdalmolood\AiSecurityGuardian\DTO\Finding;
use Abdalmolood\AiSecurityGuardian\Enums\Severity;

class CodeSecurityVisitor extends BaseVisitor
{
    protected array $dangerousFunctions = [
        'eval', 'exec', 'shell_exec', 'system', 'passthru', 
        'proc_open', 'popen', 'unserialize', 'assert'
    ];

    protected array $rawSqlMethods = [
        'raw', 'whereRaw', 'selectRaw', 'orderByRaw', 'havingRaw'
    ];

    public function enterNode(Node $node)
    {
        // 1. Dangerous Functions
        if ($node instanceof FuncCall && $node->name instanceof Node\Name) {
            $funcName = $node->name->toString();
            if (in_array(strtolower($funcName), $this->dangerousFunctions)) {
                $this->addFinding(new Finding(
                    title: 'Dangerous PHP Function Used',
                    description: "The function `{$funcName}()` was found. This can lead to remote code execution.",
                    severity: Severity::CRITICAL,
                    category: 'code_execution',
                    affectedFile: $this->currentFile,
                    affectedLine: $node->getStartLine(),
                    recommendation: 'Refactor to use safe alternatives and avoid passing user input to OS level execution functions.',
                    safeAutoFixAllowed: false,
                ));
            }
        }

        // 2. Eval (Eval is a language construct, not a normal FuncCall in AST)
        if ($node instanceof Node\Expr\Eval_) {
            $this->addFinding(new Finding(
                title: 'Dangerous PHP Function Used',
                description: "The construct `eval()` was found.",
                severity: Severity::CRITICAL,
                category: 'code_execution',
                affectedFile: $this->currentFile,
                affectedLine: $node->getStartLine(),
                recommendation: 'Never use eval() with untrusted data.',
                safeAutoFixAllowed: false,
            ));
        }

        // 3. Raw SQL
        if ($node instanceof StaticCall && $node->class instanceof Node\Name && $node->name instanceof Node\Identifier) {
            if ($node->class->toString() === 'DB' && in_array($node->name->toString(), $this->rawSqlMethods)) {
                $this->flagRawSql($node);
            }
        }

        if ($node instanceof MethodCall && $node->name instanceof Node\Identifier) {
            if (in_array($node->name->toString(), $this->rawSqlMethods)) {
                $this->flagRawSql($node);
            }

            // 4. Mass Assignment ($request->all())
            if ($node->name->toString() === 'all') {
                if ($node->var instanceof Node\Expr\Variable && is_string($node->var->name) && $node->var->name === 'request') {
                    // Check parent node for create or update? For AST it's complex, we just flag $request->all() inside controllers.
                    if (str_contains($this->currentFile, 'Controller')) {
                        $this->addFinding(new Finding(
                            title: 'Mass Assignment Risk',
                            description: "Using `\$request->all()` can lead to mass assignment vulnerabilities.",
                            severity: Severity::HIGH,
                            category: 'mass_assignment',
                            affectedFile: $this->currentFile,
                            affectedLine: $node->getStartLine(),
                            recommendation: 'Use `$request->validated()` instead of `$request->all()`.',
                            safeAutoFixAllowed: true,
                        ));
                    }
                }
            }
        }

        return null;
    }

    protected function flagRawSql(Node $node)
    {
        $this->addFinding(new Finding(
            title: 'Raw SQL Query Detected',
            description: "Raw SQL method was found. Ensure bindings are parameterized.",
            severity: Severity::HIGH,
            category: 'sql_injection',
            affectedFile: $this->currentFile,
            affectedLine: $node->getStartLine(),
            recommendation: 'Use parameterized bindings or refactor to use Eloquent query builder methods.',
            safeAutoFixAllowed: false,
        ));
    }
}
