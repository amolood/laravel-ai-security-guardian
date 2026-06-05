<?php

namespace Abdalmolood\AiSecurityGuardian\Scanners;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Abdalmolood\AiSecurityGuardian\Contracts\ScannerInterface;
use Abdalmolood\AiSecurityGuardian\DTO\Finding;
use Abdalmolood\AiSecurityGuardian\Enums\Severity;

class BladeScanner implements ScannerInterface
{
    public function getName(): string
    {
        return 'Blade Security Scanner';
    }

    public function scan(): Collection
    {
        $findings = collect();
        $viewsPath = resource_path('views');

        if (!File::exists($viewsPath)) {
            return $findings;
        }

        $files = File::allFiles($viewsPath);

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php' || !str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }

            $content = file_get_contents($file->getPathname());
            $lines = explode("\n", $content);

            foreach ($lines as $lineNumber => $line) {
                // Look for {!! ... !!} syntax
                if (preg_match('/\{!!\s*(.*?)\s*!!\}/', $line, $matches)) {
                    $variable = $matches[1];
                    
                    // Simple heuristic: if it looks like a function call or property access that isn't explicitly safe, flag it.
                    // To avoid false positives, we would ideally need a parser, but regex is a start.
                    $findings->push(new Finding(
                        title: 'Unescaped Blade Output detected',
                        description: "Raw unescaped output is used: `{!! $variable !!}`. This can lead to XSS vulnerabilities if the data contains user input.",
                        severity: Severity::HIGH,
                        category: 'blade_xss',
                        affectedFile: str_replace(base_path() . '/', '', $file->getPathname()),
                        affectedLine: $lineNumber + 1,
                        recommendation: 'Use {{ ' . $variable . ' }} instead, unless the output is explicitly sanitized using a tool like HTMLPurifier.',
                        safeAutoFixAllowed: false, // Too risky to auto-fix, could break intentional HTML
                        humanReviewRequired: true,
                    ));
                }
            }
        }

        return $findings;
    }
}
