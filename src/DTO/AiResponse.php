<?php

namespace Abdalmolood\AiSecurityGuardian\DTO;

class AiResponse
{
    /**
     * @param Finding[] $findings
     */
    public function __construct(
        public readonly array $findings,
        public readonly string $rawResponse,
        public readonly array $metadata = []
    ) {}
}
