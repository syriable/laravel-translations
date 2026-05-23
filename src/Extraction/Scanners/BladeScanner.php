<?php

declare(strict_types=1);

namespace Syriable\Translations\Extraction\Scanners;

use Syriable\Translations\Contracts\Scanner;
use Syriable\Translations\Extraction\AstKeyExtractor;

/**
 * Extracts translation keys from Blade templates.
 *
 * Rather than parsing Blade with regular expressions, the template is rewritten
 * into an equivalent PHP template (preserving line positions) and handed to the
 * same AST extractor used for PHP. Echoes, raw echoes, @php blocks and the
 * @lang / @choice directives are all understood.
 */
final readonly class BladeScanner implements Scanner
{
    public function __construct(private AstKeyExtractor $extractor) {}

    public function extensions(): array
    {
        return ['blade.php'];
    }

    public function scan(string $contents, string $relativePath): array
    {
        return $this->extractor->extract($this->toPhpTemplate($contents), $relativePath);
    }

    private function toPhpTemplate(string $blade): string
    {
        $blade = $this->stripComments($blade);
        $blade = $this->convertDirective($blade, '@lang', 'trans');
        $blade = $this->convertDirective($blade, '@choice', 'trans_choice');
        $blade = (string) preg_replace('/@php\b/', '<?php', $blade);
        $blade = (string) preg_replace('/@endphp\b/', '?>', $blade);
        $blade = (string) preg_replace_callback(
            '/\{!!\s*(.+?)\s*!!\}/s',
            static fn (array $m): string => '<?php '.$m[1].'; ?>',
            $blade,
        );

        return (string) preg_replace_callback(
            '/\{\{\s*(.+?)\s*\}\}/s',
            static fn (array $m): string => '<?php '.$m[1].'; ?>',
            $blade,
        );
    }

    private function stripComments(string $blade): string
    {
        return (string) preg_replace_callback(
            '/\{\{--.*?--\}\}/s',
            static fn (array $m): string => (string) preg_replace('/[^\n]/', ' ', $m[0]),
            $blade,
        );
    }

    /**
     * Rewrite a directive such as @lang(...) into <?php trans(...); ?>, scanning
     * for the matching closing parenthesis so multi-line arguments are kept and
     * line positions are preserved.
     */
    private function convertDirective(string $source, string $token, string $function): string
    {
        $needle = $token.'(';
        $output = '';
        $offset = 0;
        $length = strlen($source);

        while (($position = strpos($source, $needle, $offset)) !== false) {
            $output .= substr($source, $offset, $position - $offset);

            $cursor = $position + strlen($token);
            $depth = 0;
            $end = $cursor;

            for (; $end < $length; $end++) {
                $character = $source[$end];

                if ($character === '(') {
                    $depth++;
                } elseif ($character === ')') {
                    $depth--;

                    if ($depth === 0) {
                        break;
                    }
                }
            }

            $arguments = substr($source, $cursor + 1, $end - $cursor - 1);
            $output .= '<?php '.$function.'('.$arguments.'); ?>';
            $offset = $end + 1;
        }

        return $output.substr($source, $offset);
    }
}
