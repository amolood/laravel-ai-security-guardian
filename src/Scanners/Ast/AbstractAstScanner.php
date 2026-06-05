<?php

namespace Abdalmolood\AiSecurityGuardian\Scanners\Ast;

use PhpParser\Error;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use PhpParser\NodeVisitorAbstract;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Abdalmolood\AiSecurityGuardian\Contracts\ScannerInterface;

abstract class AbstractAstScanner implements ScannerInterface
{
    protected function parseFiles(string $path, NodeVisitorAbstract $visitor): Collection
    {
        $findings = collect();
        
        if (!File::exists($path)) {
            return $findings;
        }

        $files = File::isDirectory($path) ? File::allFiles($path) : [new \SplFileInfo($path)];
        
        $parser = (new ParserFactory())->createForNewestSupportedVersion();

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $code = file_get_contents($file->getPathname());
            
            try {
                $stmts = $parser->parse($code);
                
                $traverser = new NodeTraverser();
                // Pass the current file path to the visitor so it can record it
                $visitor->setCurrentFile(str_replace(base_path() . '/', '', $file->getPathname()));
                $traverser->addVisitor($visitor);
                
                $traverser->traverse($stmts);
                
            } catch (Error $e) {
                // Ignore parsing errors for individual files
                continue;
            }
        }
        
        return $visitor->getFindings();
    }
}
