<?php

namespace Abdalmolood\AiSecurityGuardian\Scanners\Ast\Visitors;

use PhpParser\NodeVisitorAbstract;
use Illuminate\Support\Collection;
use Abdalmolood\AiSecurityGuardian\DTO\Finding;

abstract class BaseVisitor extends NodeVisitorAbstract
{
    protected string $currentFile = '';
    protected Collection $findings;

    public function __construct()
    {
        $this->findings = collect();
    }

    public function setCurrentFile(string $file): void
    {
        $this->currentFile = $file;
    }

    public function getFindings(): Collection
    {
        return $this->findings;
    }

    protected function addFinding(Finding $finding): void
    {
        $this->findings->push($finding);
    }
}
