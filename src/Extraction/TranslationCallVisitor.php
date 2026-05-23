<?php

declare(strict_types=1);

namespace Syriable\Translations\Extraction;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\NodeVisitorAbstract;
use Syriable\Translations\Domain\ExtractedKey;
use Syriable\Translations\Domain\SourceReference;
use Syriable\Translations\Domain\TranslationKey;

/**
 * Collects translation helper calls (e.g. __('key'), trans('key'),
 * trans_choice('key', $n)) whose first argument is a literal string.
 *
 * Dynamic keys (variables, concatenation, method calls) are intentionally
 * ignored: they cannot be resolved statically and would produce noise.
 */
final class TranslationCallVisitor extends NodeVisitorAbstract
{
    /**
     * @var list<ExtractedKey>
     */
    public array $keys = [];

    /**
     * @param  list<string>  $functions
     */
    public function __construct(
        private readonly array $functions,
        private readonly string $relativePath,
    ) {}

    public function enterNode(Node $node): ?int
    {
        if (! $node instanceof FuncCall || ! $node->name instanceof Name) {
            return null;
        }

        $function = $node->name->toString();

        if (! in_array($function, $this->functions, true)) {
            return null;
        }

        $arguments = $node->getArgs();
        $first = $arguments[0]->value ?? null;

        if (! $first instanceof String_ || $first->value === '') {
            return null;
        }

        $this->keys[] = new ExtractedKey(
            new TranslationKey($first->value),
            [new SourceReference($this->relativePath, $node->getStartLine(), $function)],
            $function === 'trans_choice',
        );

        return null;
    }
}
