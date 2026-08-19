<?php

declare(strict_types=1);

namespace Modules\AgroCalendar\Enums;

enum NutrientEffectType: string
{
    case DEPLETE = 'deplete';
    case ADD = 'add';

    public function label(): array
    {
        return match ($this) {
            self::DEPLETE => [
                'uz' => 'Kamaytiradi',
                'ru' => 'Истощает',
                'en' => 'Depletes',
            ],
            self::ADD => [
                'uz' => 'Qo\'shadi',
                'ru' => 'Добавляет',
                'en' => 'Adds',
            ],
        };
    }

    public function isPositive(): bool
    {
        return $this === self::ADD;
    }

    public function isNegative(): bool
    {
        return $this === self::DEPLETE;
    }

    /**
     * @return array<string, string>
     */
    public static function options(?string $locale = null): array
    {
        $locale = $locale ?? app()->getLocale();

        return collect(self::cases())
            ->mapWithKeys(fn (self $type) => [
                $type->value => $type->label()[$locale] ?? $type->label()['en'],
            ])
            ->all();
    }
}
