<?php

namespace Abdalmolood\AiSecurityGuardian\AI;

use Illuminate\Support\Str;

class PromptBuilder
{
    public function getSystemPrompt(): string
    {
        return <<<PROMPT
You are a defensive Laravel security reviewer.
Return only a JSON array.
Classify each finding as critical, high, medium, low, or info.
Do not generate exploit code, offensive payloads, or direct production changes.
Each item must contain: title, severity, category, affected_file, affected_line, description, business_impact, technical_impact, recommended_fix, safe_auto_fix_allowed, human_review_required, test_plan, references.
PROMPT;
    }

    public function buildPrompt(string $task, array $context = [], array $options = []): string
    {
        $context = $this->compactContext($context, $options);

        $prompt = "Task: $task\n\n";
        
        if (!empty($context)) {
            $prompt .= "Context:\n" . json_encode($context, JSON_UNESCAPED_SLASHES) . "\n\n";
        }

        $prompt .= "Return only the JSON array. Prefer fewer, higher-confidence findings over verbose commentary.";

        return $prompt;
    }

    public function compactContext(array $context, array $options = []): array
    {
        $maxFindings = max(1, (int) ($options['max_findings'] ?? config('ai-security-guardian.ai.max_findings_per_request', 12)));
        $maxTextLength = max(80, (int) ($options['max_text_length'] ?? config('ai-security-guardian.ai.max_text_length', 360)));
        $maxReferences = max(0, (int) ($options['max_references'] ?? config('ai-security-guardian.ai.max_references', 3)));

        if ($this->isList($context)) {
            $findings = $this->normalizeFindings($context, $maxTextLength, $maxReferences);
            $findings = $this->dedupeFindings($findings);
            $findings = $this->prioritizeFindings($findings);
            $total = count($findings);

            return [
                'scan_summary' => $this->summarizeFindings($findings),
                'findings' => array_slice($findings, 0, $maxFindings),
                'truncated_findings' => max(0, $total - $maxFindings),
            ];
        }

        $compact = [];
        foreach ($context as $key => $value) {
            if ($key === 'findings' && is_array($value)) {
                $compact[$key] = $this->compactContext($value, $options);
                continue;
            }

            $compact[$key] = $this->normalizeValue($value, $maxTextLength, $maxReferences);
        }

        return array_filter($compact, static fn ($value) => $value !== null && $value !== [] && $value !== '');
    }

    protected function normalizeFindings(array $findings, int $maxTextLength, int $maxReferences): array
    {
        return array_values(array_filter(array_map(function ($finding) use ($maxTextLength, $maxReferences) {
            $record = is_object($finding) ? get_object_vars($finding) : (array) $finding;

            $normalized = [
                'title' => $this->stringValue($record, ['title'], $maxTextLength),
                'severity' => $this->stringValue($record, ['severity'], $maxTextLength, 'info'),
                'category' => $this->stringValue($record, ['category'], $maxTextLength, 'general'),
                'affected_file' => $this->stringValue($record, ['affectedFile', 'affected_file'], $maxTextLength),
                'affected_line' => $this->integerValue($record, ['affectedLine', 'affected_line']),
                'package_name' => $this->stringValue($record, ['packageName', 'package_name'], $maxTextLength),
                'cve' => $this->stringValue($record, ['cve'], $maxTextLength),
                'advisory_url' => $this->stringValue($record, ['advisoryUrl', 'advisory_url'], $maxTextLength),
                'description' => $this->stringValue($record, ['description'], $maxTextLength),
                'business_impact' => $this->stringValue($record, ['businessImpact', 'business_impact'], $maxTextLength),
                'technical_impact' => $this->stringValue($record, ['technicalImpact', 'technical_impact'], $maxTextLength),
                'recommendation' => $this->stringValue($record, ['recommendation', 'recommended_fix'], $maxTextLength),
                'test_plan' => $this->stringValue($record, ['testPlan', 'test_plan'], $maxTextLength),
                'status' => $this->stringValue($record, ['status'], $maxTextLength),
                'safe_auto_fix_allowed' => (bool) ($this->value($record, ['safeAutoFixAllowed', 'safe_auto_fix_allowed']) ?? false),
                'human_review_required' => (bool) ($this->value($record, ['humanReviewRequired', 'human_review_required']) ?? true),
                'references' => array_slice(array_values(array_filter((array) ($this->value($record, ['references']) ?? []))), 0, $maxReferences),
            ];

            return array_filter($normalized, static fn ($value) => $value !== null && $value !== [] && $value !== '');
        }, $findings)));
    }

    protected function summarizeFindings(array $findings): array
    {
        $summary = [
            'total_findings' => count($findings),
            'critical' => 0,
            'high' => 0,
            'medium' => 0,
            'low' => 0,
            'info' => 0,
        ];

        foreach ($findings as $finding) {
            $severity = strtolower((string) ($finding['severity'] ?? 'info'));
            if (array_key_exists($severity, $summary)) {
                $summary[$severity]++;
            }
        }

        return $summary;
    }

    protected function dedupeFindings(array $findings): array
    {
        $seen = [];
        $deduped = [];

        foreach ($findings as $finding) {
            $fingerprint = sha1(implode('|', [
                strtolower((string) ($finding['severity'] ?? 'info')),
                strtolower((string) ($finding['category'] ?? '')),
                strtolower((string) ($finding['title'] ?? '')),
                strtolower((string) ($finding['affected_file'] ?? '')),
                (string) ($finding['affected_line'] ?? ''),
            ]));

            if (isset($seen[$fingerprint])) {
                continue;
            }

            $seen[$fingerprint] = true;
            $deduped[] = $finding;
        }

        return $deduped;
    }

    protected function prioritizeFindings(array $findings): array
    {
        usort($findings, function (array $a, array $b) {
            return $this->severityWeight($b['severity'] ?? 'info') <=> $this->severityWeight($a['severity'] ?? 'info');
        });

        return $findings;
    }

    protected function severityWeight(string $severity): int
    {
        return match (strtolower($severity)) {
            'critical' => 5,
            'high' => 4,
            'medium' => 3,
            'low' => 2,
            default => 1,
        };
    }

    protected function normalizeValue(mixed $value, int $maxTextLength, int $maxReferences): mixed
    {
        if (is_string($value)) {
            return Str::limit(trim($value), $maxTextLength, '…');
        }

        if (is_array($value)) {
            if ($this->isList($value) && $this->looksLikeFindingList($value)) {
                return $this->compactContext($value, [
                    'max_findings' => config('ai-security-guardian.ai.max_findings_per_request', 12),
                    'max_text_length' => $maxTextLength,
                    'max_references' => $maxReferences,
                ]);
            }

            $normalized = [];
            foreach ($value as $key => $item) {
                $normalized[$key] = $this->normalizeValue($item, $maxTextLength, $maxReferences);
            }

            return array_filter($normalized, static fn ($item) => $item !== null && $item !== [] && $item !== '');
        }

        if (is_object($value)) {
            return $this->normalizeValue(get_object_vars($value), $maxTextLength, $maxReferences);
        }

        return $value;
    }

    protected function value(array $record, array $keys): mixed
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $record)) {
                return $record[$key];
            }
        }

        return null;
    }

    protected function stringValue(array $record, array $keys, int $maxTextLength, ?string $default = null): ?string
    {
        $value = $this->value($record, $keys);

        if ($value === null || $value === '') {
            return $default;
        }

        return Str::limit(trim((string) $value), $maxTextLength, '…');
    }

    protected function integerValue(array $record, array $keys): ?int
    {
        $value = $this->value($record, $keys);

        return $value === null || $value === '' ? null : (int) $value;
    }

    protected function isList(array $value): bool
    {
        return array_keys($value) === range(0, count($value) - 1);
    }

    protected function looksLikeFindingList(array $value): bool
    {
        foreach (array_slice($value, 0, 3) as $item) {
            if (! is_array($item) && ! is_object($item)) {
                return false;
            }
        }

        return true;
    }
}
