<?php

namespace Abdalmolood\AiSecurityGuardian\Scanners\Ast\Visitors;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use Abdalmolood\AiSecurityGuardian\DTO\Finding;
use Abdalmolood\AiSecurityGuardian\Enums\Severity;

class QueueVisitor extends BaseVisitor
{
    public function enterNode(Node $node)
    {
        if ($node instanceof Class_ && $node->implements) {
            $isJob = false;
            foreach ($node->implements as $interface) {
                if (str_ends_with($interface->toString(), 'ShouldQueue')) {
                    $isJob = true;
                    break;
                }
            }

            if ($isJob) {
                $hasTries = false;
                foreach ($node->getProperties() as $prop) {
                    if ($prop->props[0]->name->toString() === 'tries') {
                        $hasTries = true;
                        break;
                    }
                }

                if (!$hasTries) {
                    $this->addFinding(new Finding(
                        title: 'Queue Job Missing Retry Limit',
                        description: "The job `{$node->name->toString()}` implements ShouldQueue but does not define a `\$tries` property. This can cause infinite retry loops if it fails, potentially causing Denial of Service or exhausting queue workers.",
                        severity: Severity::MEDIUM,
                        category: 'queue_security',
                        affectedFile: $this->currentFile,
                        affectedLine: $node->getStartLine(),
                        recommendation: 'Add `public $tries = 3;` (or a suitable number) to the Job class.',
                        safeAutoFixAllowed: true,
                    ));
                }
            }
        }

        return null;
    }
}
