<?php

namespace Abdalmolood\AiSecurityGuardian\Contracts;

use Illuminate\Support\Collection;

interface ScannerInterface
{
    /**
     * Get the scanner's unique name.
     */
    public function getName(): string;

    /**
     * Run the scanner and return a collection of findings (DTO\Finding).
     *
     * @return Collection
     */
    public function scan(): Collection;
}
