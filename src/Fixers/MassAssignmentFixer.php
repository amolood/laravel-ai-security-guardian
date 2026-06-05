<?php

namespace Abdalmolood\AiSecurityGuardian\Fixers;

use Illuminate\Support\Facades\File;
use PhpParser\Node;
use PhpParser\NodeFinder;
use PhpParser\ParserFactory;
use Abdalmolood\AiSecurityGuardian\Contracts\FixerInterface;
use Abdalmolood\AiSecurityGuardian\Models\SecurityFinding;

/**
 * Rewrites `$request->all()` / `request()->all()` to `->validated()` on the
 * reported line.
 *
 * This is AST-verified: it only edits a call whose receiver is an HTTP request
 * (a `$request`-named variable, a `request()` helper call, or `$this->request`).
 * A blind string replace would corrupt unrelated `->all()` calls such as
 * `Collection::all()`, which do not have a `validated()` method.
 */
class MassAssignmentFixer implements FixerInterface
{
    public function apply(SecurityFinding $finding): bool
    {
        if ($finding->category !== 'mass_assignment' || !$finding->affected_file) {
            return false;
        }

        $filePath = base_path($finding->affected_file);

        if (!File::exists($filePath)) {
            return false;
        }

        $lineNumber = $finding->affected_line;
        if (!$lineNumber) {
            return false;
        }

        $content = File::get($filePath);

        $call = $this->findRequestAllCallOnLine($content, (int) $lineNumber);
        if ($call === null) {
            return false;
        }

        $lines = explode("\n", $content);
        if (!isset($lines[$lineNumber - 1]) || !str_contains($lines[$lineNumber - 1], '->all()')) {
            return false;
        }

        // Replace only the first `->all()` on the verified line.
        $pos = strpos($lines[$lineNumber - 1], '->all()');
        $lines[$lineNumber - 1] = substr_replace($lines[$lineNumber - 1], '->validated()', $pos, strlen('->all()'));

        File::put($filePath, implode("\n", $lines));

        return true;
    }

    /**
     * Returns the MethodCall node for `<request>->all()` on the given line, or
     * null if no such request-receiver call exists there.
     */
    protected function findRequestAllCallOnLine(string $code, int $line): ?Node\Expr\MethodCall
    {
        $parser = (new ParserFactory())->createForNewestSupportedVersion();

        try {
            $ast = $parser->parse($code);
        } catch (\Throwable $e) {
            return null;
        }

        if ($ast === null) {
            return null;
        }

        $finder = new NodeFinder();

        /** @var Node\Expr\MethodCall[] $calls */
        $calls = $finder->find($ast, function (Node $node) use ($line): bool {
            return $node instanceof Node\Expr\MethodCall
                && $node->name instanceof Node\Identifier
                && $node->name->toString() === 'all'
                && $node->getStartLine() === $line;
        });

        foreach ($calls as $call) {
            if ($this->isRequestReceiver($call->var)) {
                return $call;
            }
        }

        return null;
    }

    /**
     * True when the receiver of the call is recognisably an HTTP request.
     */
    protected function isRequestReceiver(Node $receiver): bool
    {
        // $request->all() / $req->all() — variable whose name contains "request".
        if ($receiver instanceof Node\Expr\Variable && is_string($receiver->name)) {
            return str_contains(strtolower($receiver->name), 'request')
                || strtolower($receiver->name) === 'req';
        }

        // request()->all()
        if ($receiver instanceof Node\Expr\FuncCall
            && $receiver->name instanceof Node\Name
            && $receiver->name->toString() === 'request') {
            return true;
        }

        // $this->request->all()
        if ($receiver instanceof Node\Expr\PropertyFetch
            && $receiver->name instanceof Node\Identifier) {
            return str_contains(strtolower($receiver->name->toString()), 'request');
        }

        return false;
    }
}
