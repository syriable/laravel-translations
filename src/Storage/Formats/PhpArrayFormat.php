<?php

declare(strict_types=1);

namespace Syriable\Translations\Storage\Formats;

use Illuminate\Support\Arr;
use PhpParser\ConstExprEvaluationException;
use PhpParser\ConstExprEvaluator;
use PhpParser\Error as ParserError;
use PhpParser\Node\ArrayItem;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Stmt\Return_;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use Syriable\Translations\Contracts\FileFormat;

/**
 * Reads and writes Laravel PHP array language files.
 *
 * Parsing evaluates the file's `return [...]` expression through the AST
 * constant evaluator rather than `require`-ing the file, so reading a catalog
 * never executes project code.
 *
 * Each array entry is evaluated independently: a single non-constant value
 * (a function call, a runtime concatenation, ...) only skips that one entry
 * instead of discarding the whole file.
 */
final class PhpArrayFormat implements FileFormat
{
    private readonly Parser $parser;

    private readonly ConstExprEvaluator $evaluator;

    public function __construct()
    {
        $this->parser = (new ParserFactory)->createForNewestSupportedVersion();
        $this->evaluator = new ConstExprEvaluator;
    }

    public function extension(): string
    {
        return 'php';
    }

    public function parse(string $contents): array
    {
        try {
            $ast = $this->parser->parse($contents);
        } catch (ParserError) {
            return [];
        }

        if ($ast === null) {
            return [];
        }

        $expression = $this->returnExpression($ast);

        if (! $expression instanceof Array_) {
            return [];
        }

        return $this->flatten($this->evaluateArray($expression));
    }

    public function dump(array $entries): string
    {
        $nested = Arr::undot($entries);

        return "<?php\n\ndeclare(strict_types=1);\n\nreturn ".$this->render($nested, 1).";\n";
    }

    /**
     * @param  array<int, \PhpParser\Node\Stmt>  $ast
     */
    private function returnExpression(array $ast): ?Expr
    {
        foreach ($ast as $statement) {
            if ($statement instanceof Return_ && $statement->expr !== null) {
                return $statement->expr;
            }
        }

        return null;
    }

    /**
     * Evaluate an array literal entry by entry so a single unevaluable value
     * does not discard its siblings. Nested arrays are recursed into for the
     * same reason.
     *
     * @return array<array-key, mixed>
     */
    private function evaluateArray(Array_ $array): array
    {
        $result = [];
        $autoIndex = 0;

        foreach ($array->items as $item) {
            if ($item->unpack) {
                continue;
            }

            $key = $this->resolveKey($item, $autoIndex);

            if ($key === null) {
                continue;
            }

            if (is_int($key) && $key >= $autoIndex) {
                $autoIndex = $key + 1;
            }

            if ($item->value instanceof Array_) {
                $result[$key] = $this->evaluateArray($item->value);

                continue;
            }

            try {
                $result[$key] = $this->evaluator->evaluateSilently($item->value);
            } catch (ConstExprEvaluationException) {
                continue;
            }
        }

        return $result;
    }

    private function resolveKey(ArrayItem $item, int $autoIndex): int|string|null
    {
        if ($item->key === null) {
            return $autoIndex;
        }

        try {
            $key = $this->evaluator->evaluateSilently($item->key);
        } catch (ConstExprEvaluationException) {
            return null;
        }

        return is_int($key) || is_string($key) ? $key : null;
    }

    /**
     * @param  array<array-key, mixed>  $value
     * @return array<string, string|null>
     */
    private function flatten(array $value): array
    {
        $flat = [];

        foreach (Arr::dot($value) as $key => $item) {
            if (is_array($item)) {
                continue;
            }

            $flat[(string) $key] = $item === null ? null : (string) $item;
        }

        return $flat;
    }

    /**
     * @param  array<array-key, mixed>  $array
     */
    private function render(array $array, int $depth): string
    {
        if ($array === []) {
            return '[]';
        }

        $indent = str_repeat('    ', $depth);
        $closing = str_repeat('    ', $depth - 1);
        $lines = [];

        foreach ($array as $key => $value) {
            $renderedValue = is_array($value)
                ? $this->render($value, $depth + 1)
                : var_export($value, true);

            $lines[] = $indent.var_export($key, true).' => '.$renderedValue.',';
        }

        return "[\n".implode("\n", $lines)."\n".$closing.']';
    }
}
