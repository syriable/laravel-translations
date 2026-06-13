<?php

namespace Syriable\Translations\Files;

class LangWriter
{
    public function writePhp(string $path, array $dotted, bool $sortKeys = true): bool
    {
        $nested = $this->inflate($dotted);

        if ($sortKeys) {
            $this->ksortRecursive($nested);
        }

        $contents = "<?php\n\nreturn ".$this->export($nested).";\n";

        return $this->put($path, $contents);
    }

    public function writeJson(string $path, array $values, bool $sortKeys = true): bool
    {
        if ($sortKeys) {
            ksort($values);
        }

        $contents = json_encode($values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return $this->put($path, $contents."\n");
    }

    private function inflate(array $dotted): array
    {
        $result = [];

        foreach ($dotted as $key => $value) {
            data_set($result, $key, $value);
        }

        return $result;
    }

    private function ksortRecursive(array &$array): void
    {
        ksort($array);

        foreach ($array as &$value) {
            if (is_array($value)) {
                $this->ksortRecursive($value);
            }
        }
    }

    private function export(array $array, int $depth = 1): string
    {
        $indent = str_repeat('    ', $depth);
        $closingIndent = str_repeat('    ', $depth - 1);
        $lines = [];

        $isList = array_is_list($array);

        foreach ($array as $key => $value) {
            $formattedKey = $isList ? '' : var_export((string) $key, true).' => ';

            if (is_array($value)) {
                $lines[] = $indent.$formattedKey.$this->export($value, $depth + 1);

                continue;
            }

            $lines[] = $indent.$formattedKey.var_export($value, true);
        }

        return "[\n".implode(",\n", $lines).",\n".$closingIndent.']';
    }

    private function put(string $path, string $contents): bool
    {
        $directory = dirname($path);

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        return file_put_contents($path, $contents) !== false;
    }
}
