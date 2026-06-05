<?php

namespace Abdalmolood\AiSecurityGuardian\DTO;

use Illuminate\Support\Collection;
use Carbon\Carbon;

class ScanResult
{
    public function __construct(
        public readonly Carbon $startedAt,
        public readonly Carbon $finishedAt,
        public readonly Collection $findings,
        public readonly int $riskScore,
        public readonly array $summary,
        public readonly string $provider,
        public readonly string $model
    ) {}
}
