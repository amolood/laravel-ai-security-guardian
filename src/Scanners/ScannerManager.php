<?php

namespace Abdalmolood\AiSecurityGuardian\Scanners;

use Illuminate\Support\Collection;
use Abdalmolood\AiSecurityGuardian\Contracts\ScannerInterface;

class ScannerManager
{
    /** @var ScannerInterface[] */
    protected array $scanners = [];

    public function registerScanner(ScannerInterface $scanner): void
    {
        $this->scanners[] = $scanner;
    }

    /**
     * Run all registered scanners and return a merged collection of findings.
     *
     * @return Collection
     */
    public function scan(): Collection
    {
        $allFindings = collect();

        foreach ($this->scanners as $scanner) {
            $findings = $scanner->scan();
            $allFindings = $allFindings->merge($findings);
        }

        return $allFindings;
    }

    public function getScanners(): array
    {
        return $this->scanners;
    }
}
