<?php

namespace Syriable\Translations\Support;

use Syriable\Translations\Enums\MessageStatus;
use Syriable\Translations\Models\Locale;
use Syriable\Translations\Models\Message;
use Syriable\Translations\Models\Phrase;

class MessageSeeder
{
    public function seedAll(): int
    {
        $created = 0;

        foreach (Locale::query()->enabled()->get() as $locale) {
            $created += $this->seedLocale($locale);
        }

        return $created;
    }

    public function seedLocale(Locale $locale): int
    {
        $created = 0;

        Phrase::query()
            ->whereDoesntHave('messages', fn (\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder => $query->where('locale_id', $locale->id))
            ->chunkById(500, function (\Illuminate\Support\Collection $phrases) use ($locale, &$created): void {
                foreach ($phrases as $phrase) {
                    Message::query()->create([
                        'phrase_id' => $phrase->id,
                        'locale_id' => $locale->id,
                        'value' => null,
                        'status' => MessageStatus::Open,
                    ]);

                    $created++;
                }
            });

        return $created;
    }

    public function seedPhrase(Phrase $phrase, ?string $sourceValue = null): void
    {
        $sourceId = optional(Locale::source())->id;

        foreach (Locale::query()->enabled()->get() as $locale) {
            $isSource = $locale->id === $sourceId;

            Message::query()->firstOrCreate(
                ['phrase_id' => $phrase->id, 'locale_id' => $locale->id],
                [
                    'value' => $isSource ? $sourceValue : null,
                    'status' => $isSource && $sourceValue !== null ? MessageStatus::Draft : MessageStatus::Open,
                ],
            );
        }
    }
}
