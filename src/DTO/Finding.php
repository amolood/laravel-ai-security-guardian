<?php

namespace Abdalmolood\AiSecurityGuardian\DTO;

use Abdalmolood\AiSecurityGuardian\Enums\Severity;
use Abdalmolood\AiSecurityGuardian\Enums\FindingStatus;

class Finding
{
    public function __construct(
        public readonly string $title,
        public readonly string $description,
        public readonly Severity $severity,
        public readonly string $category,
        public readonly ?string $affectedFile = null,
        public readonly ?int $affectedLine = null,
        public readonly ?string $packageName = null,
        public readonly ?string $cve = null,
        public readonly ?string $advisoryUrl = null,
        public readonly ?string $recommendation = null,
        public readonly bool $safeAutoFixAllowed = false,
        public readonly bool $humanReviewRequired = true,
        public readonly FindingStatus $status = FindingStatus::OPEN,
        public readonly ?string $businessImpact = null,
        public readonly ?string $technicalImpact = null,
        public readonly ?string $testPlan = null,
        public readonly array $references = []
    ) {}
}
