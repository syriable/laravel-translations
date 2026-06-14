<?php

use Syriable\Translations\Scanning\Loose\FalsePositiveFilter;

beforeEach(function (): void {
    $this->filter = new FalsePositiveFilter;
});

it('rejects non-translatable strings', function (string $text): void {
    expect($this->filter->rejects($text))->toBeTrue();
})->with([
    'empty' => '',
    'too short' => 'Hi',
    'single word' => 'Configuration',
    'digits and punctuation' => '12:34 - 56',
    'code identifier' => 'Foo::BAR',
    'url' => 'https://example.com/welcome',
    'mention' => '@team please review',
    'variable' => '$user->name here',
]);

it('accepts real user-facing strings', function (string $text): void {
    expect($this->filter->rejects($text))->toBeFalse();
})->with([
    'Welcome to the dashboard',
    'Save changes',
    'Sign in',
    'Order #42 was placed',
]);
