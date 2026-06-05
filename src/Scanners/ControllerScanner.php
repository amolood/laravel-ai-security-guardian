<?php

namespace Abdalmolood\AiSecurityGuardian\Scanners;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Abdalmolood\AiSecurityGuardian\Contracts\ScannerInterface;
use Abdalmolood\AiSecurityGuardian\DTO\Finding;
use Abdalmolood\AiSecurityGuardian\Enums\Severity;

class ControllerScanner implements ScannerInterface
{
    protected array $dangerousFunctions = [
        'eval(', 'exec(', 'shell_exec(', 'system(', 'passthru(', 
        'proc_open(', 'popen(', 'unserialize(', 'assert('
    ];

    protected array $rawSqlMethods = [
        'DB::raw(', '->whereRaw(', '->selectRaw(', '->orderByRaw(', '->havingRaw('
    ];

    protected array $massAssignmentMethods = [
        '::create($request->all())', '->update($request->all())'
    ];

    public function getName(): string
    {
        return 'Code Analysis Scanner';
    }

    public function scan(): Collection
    {
        $findings = collect();
        $appPath = app_path();

        if (!File::exists($appPath)) {
            return $findings;
        }

        $files = File::allFiles($appPath);

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $content = file_get_contents($file->getPathname());
            $lines = explode("\n", $content);
            $filePath = str_replace(base_path() . '/', '', $file->getPathname());

            foreach ($lines as $lineNumber => $line) {
                $this->scanLine($line, $lineNumber + 1, $filePath, $findings);
            }
        }

        return $findings;
    }

    protected function scanLine(string $line, int $lineNumber, string $filePath, Collection $findings): void
    {
        // Check dangerous functions
        foreach ($this->dangerousFunctions as $func) {
            if (str_contains($line, $func)) {
                $findings->push(new Finding(
                    title: 'Dangerous PHP Function Used',
                    description: "The function `$func)` was found. This can lead to remote code execution if user input is passed.",
                    severity: Severity::CRITICAL,
                    category: 'code_execution',
                    affectedFile: $filePath,
                    affectedLine: $lineNumber,
                    recommendation: 'Refactor to use safe alternatives and avoid passing user input to OS level execution functions.',
                    safeAutoFixAllowed: false,
                ));
            }
        }

        // Check raw SQL
        foreach ($this->rawSqlMethods as $method) {
            if (str_contains($line, $method)) {
                $findings->push(new Finding(
                    title: 'Raw SQL Query Detected',
                    description: "Raw SQL `$method` was found. This can lead to SQL injection if user input is unescaped.",
                    severity: Severity::HIGH,
                    category: 'sql_injection',
                    affectedFile: $filePath,
                    affectedLine: $lineNumber,
                    recommendation: 'Ensure that parameterized bindings are used or refactor to use Eloquent query builder methods.',
                    safeAutoFixAllowed: false,
                ));
            }
        }

        // Check mass assignment
        foreach ($this->massAssignmentMethods as $method) {
            if (str_contains(str_replace(' ', '', $line), $method)) {
                $findings->push(new Finding(
                    title: 'Mass Assignment Risk',
                    description: "Using `$request->all()` in creation or update methods can lead to mass assignment vulnerabilities.",
                    severity: Severity::HIGH,
                    category: 'mass_assignment',
                    affectedFile: $filePath,
                    affectedLine: $lineNumber,
                    recommendation: 'Use `$request->validated()` instead of `$request->all()`.',
                    safeAutoFixAllowed: true,
                ));
            }
        }
    }
}
