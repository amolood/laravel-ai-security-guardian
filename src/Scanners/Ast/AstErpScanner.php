<?php

namespace Abdalmolood\AiSecurityGuardian\Scanners\Ast;

use Illuminate\Support\Collection;
use Abdalmolood\AiSecurityGuardian\Scanners\Ast\Visitors\ErpVisitor;

class AstErpScanner extends AbstractAstScanner
{
    public function getName(): string
    {
        return 'AST ERP Business Logic Scanner';
    }

    public function scan(): Collection
    {
        $visitor = new ErpVisitor();
        // Specifically target ERP-like controllers
        $path = app_path('Http/Controllers');
        $files = \Illuminate\Support\Facades\File::exists($path) ? \Illuminate\Support\Facades\File::allFiles($path) : [];
        
        $targetFiles = [];
        foreach ($files as $file) {
            $name = strtolower($file->getFilename());
            if (str_contains($name, 'invoice') || str_contains($name, 'journal') || str_contains($name, 'payment') || str_contains($name, 'order')) {
                $targetFiles[] = clone $file;
            }
        }
        
        $findings = collect();
        foreach ($targetFiles as $file) {
            $findings = $findings->merge($this->parseFiles($file->getPathname(), $visitor));
        }
        
        return $findings;
    }
}
