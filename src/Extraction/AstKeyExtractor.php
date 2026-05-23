<?php

declare(strict_types=1);

namespace Syriable\Translations\Extraction;

use PhpParser\Error as ParserError;
use PhpParser\NodeTraverser;
use PhpParser\Parser;
use PhpParser\ParserFactory;

/**
 * Parses PHP source (including templates with inline HTML) and extracts every
 * static translation key via {@see TranslationCallVisitor}. Shared by every
 * scanner so that PHP and Blade detection behave identically.
 */
final class AstKeyExtractor
{
    private readonly Parser $parser;

    /**
     * @param  list<string>  $functions
     */
    public function __construct(
        private readonly array $functions = ['__', 'trans', 'trans_choice'],
    ) {
        $this->parser = (new ParserFactory)->createForNewestSupportedVersion();
    }

    /**
     * @return list<\Syriable\Translations\Domain\ExtractedKey>
     */
    public function extract(string $code, string $relativePath): array
    {
        try {
            $ast = $this->parser->parse($code);
        } catch (ParserError) {
            return [];
        }

        if ($ast === null) {
            return [];
        }

        $visitor = new TranslationCallVisitor($this->functions, $relativePath);

        $traverser = new NodeTraverser;
        $traverser->addVisitor($visitor);
        $traverser->traverse($ast);

        return $visitor->keys;
    }
}
