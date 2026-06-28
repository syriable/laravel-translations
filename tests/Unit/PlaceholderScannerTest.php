<?php

use Syriable\Translations\Support\PlaceholderScanner;

beforeEach(function (): void {
    $this->scanner = new PlaceholderScanner;
});

it('extracts colon and brace placeholders', function (): void {
    expect($this->scanner->placeholders('Hi :name, you have {count} messages and :name again'))
        ->toEqualCanonicalizing([':name', '{count}']);
});

it('ignores time-like colons inside words', function (): void {
    expect($this->scanner->placeholders('Visit https://example.com now'))
        ->toBe([]);
});

it('detects html, plurals, urls and emails', function (): void {
    expect($this->scanner->hasHtml('Click <a href="#">here</a>'))->toBeTrue();
    expect($this->scanner->isPlural('one apple|many apples'))->toBeTrue();
    expect($this->scanner->pluralSegments('one|two|three'))->toBe(3);
    expect($this->scanner->urls('See https://laravel.com/docs here'))->toBe(['https://laravel.com/docs']);
    expect($this->scanner->emails('Mail us at hi@example.com today'))->toBe(['hi@example.com']);
});

it('extracts explicit plural selectors per segment', function (): void {
    expect($this->scanner->pluralQualifiers('{0} none|[1,19] some|[20,*] <span>many</span>'))
        ->toBe(['{0}', '[1,19]', '[20,*]']);
});

it('normalizes whitespace inside plural selectors', function (): void {
    expect($this->scanner->pluralQualifiers('{ 0 } none|[1, 19] some'))
        ->toBe(['{0}', '[1,19]']);
});

it('returns empty selectors for simple plurals', function (): void {
    expect($this->scanner->pluralQualifiers('one apple|many apples'))
        ->toBe(['', '']);
});

it('lists html tag names', function (): void {
    expect($this->scanner->htmlTags('<strong>Bold</strong> and <em>x</em>'))
        ->toEqualCanonicalizing(['strong', 'strong', 'em', 'em']);
});
