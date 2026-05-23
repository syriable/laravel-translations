<?php

declare(strict_types=1);

namespace Syriable\Translations\Storage\Formats;

use Illuminate\Support\Arr;
use PhpParser\ConstExprEvaluationException;
use PhpParser\ConstExprEvaluator;
use PhpParser\Error as ParserError;
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
 */
final class PhpArrayFormat implements FileFormat
{
    private readonly Parser $parser;

    public function __construct()
    {
        $this->parser = (new ParserFactory)->createForNewestSupportedVersion();
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

        $value = $this->evaluateReturn($ast);

        if (! is_array($value)) {
            return [];
        }

        return $this->flatten($value);
    }

    public function dump(array $entries): string
    {
        $nested = Arr::undot($entries);

        return "<?php\n\ndeclare(strict_types=1);\n\nreturn ".$this->render($nested, 1).";\n";
    }

    /**
     * @param  array<int, \PhpParser\Node\Stmt>  $ast
     */
    private function evaluateReturn(array $ast): mixed
    {
        foreach ($ast as $statement) {
            if (! $statement instanceof Return_ || $statement->expr === null) {
                continue;
            }

            try {
                return (new ConstExprEvaluator)->evaluateSilently($statement->expr);
            } catch (ConstExprEvaluationException) {
                return null;
            }
        }

        return null;
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
