<?php

namespace Syriable\Translations\Ai;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Temperature;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;
use Syriable\Translations\Support\TranslationRequest;

#[Temperature(0.3)]
#[MaxTokens(2000)]
class PhraseTranslationAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    public function __construct(
        private readonly TranslationRequest $request,
        private readonly PromptBuilder $prompts = new PromptBuilder,
    ) {}

    public function instructions(): Stringable|string
    {
        return $this->prompts->build($this->request);
    }

    public function schema(JsonSchema $schema): array
    {
        $count = max(1, $this->request->variants);

        return [
            'suggestions' => $schema->array()->items(
                $schema->object([
                    'value' => $schema->string()->required(),
                    'base_value' => $schema->string()->required(),
                    'confidence' => $schema->number()->min(0)->max(1)->required(),
                    'recommended' => $schema->boolean(),
                    'note' => $schema->string(),
                ])->withoutAdditionalProperties()
            )->min($count)->max($count)->required(),
        ];
    }
}
