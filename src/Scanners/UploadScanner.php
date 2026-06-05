<?php

namespace Abdalmolood\AiSecurityGuardian\Scanners;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Abdalmolood\AiSecurityGuardian\Contracts\ScannerInterface;
use Abdalmolood\AiSecurityGuardian\DTO\Finding;
use Abdalmolood\AiSecurityGuardian\Enums\Severity;

class UploadScanner implements ScannerInterface
{
    protected array $uploadMethods = [
        '->file(', '->hasFile(', '->store(', '->storeAs('
    ];

    public function getName(): string
    {
        return 'File Upload Security Scanner';
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

            // Simple heuristic to find upload without mimes/mimetypes validation
            $hasUpload = false;
            $hasValidation = false;
            $uploadLine = 0;

            foreach ($lines as $lineNumber => $line) {
                foreach ($this->uploadMethods as $method) {
                    if (str_contains($line, $method)) {
                        $hasUpload = true;
                        $uploadLine = $lineNumber + 1;
                    }
                }

                if ($hasUpload && (str_contains($line, 'mimes:') || str_contains($line, 'mimetypes:') || str_contains($line, '->validate('))) {
                    $hasValidation = true;
                }
            }

            if ($hasUpload && !$hasValidation) {
                 $findings->push(new Finding(
                    title: 'Unvalidated File Upload detected',
                    description: "File upload logic detected without explicit MIME type validation in the same file. Ensure files are validated.",
                    severity: Severity::HIGH,
                    category: 'file_upload',
                    affectedFile: $filePath,
                    affectedLine: $uploadLine,
                    recommendation: 'Use `mimes:png,jpg,pdf` or similar validation rules before storing the file.',
                    safeAutoFixAllowed: false, // AI needs to review the context to add proper validation
                ));
            }
        }

        return $findings;
    }
}
