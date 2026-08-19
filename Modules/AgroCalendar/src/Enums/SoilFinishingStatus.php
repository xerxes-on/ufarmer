<?php

declare(strict_types=1);

namespace Modules\AgroCalendar\Enums;

enum SoilFinishingStatus: string
{
    case LEFT_AS_IS = 'left_as_is';
    case PLOWED = 'plowed';
    case COVER_CROP = 'cover_crop';
    case MULCHED = 'mulched';
    case FERTILIZED = 'fertilized';
    case PREPARED_FOR_NEXT = 'prepared_for_next';

    /**
     * Get status label translations.
     *
     * @return array{uz: string, ru: string, en: string}
     */
    public function label(): array
    {
        return match ($this) {
            self::LEFT_AS_IS => [
                'uz' => "O'zgartirmay qoldirildi",
                'ru' => 'Оставлено как есть',
                'en' => 'Left as is',
            ],
            self::PLOWED => [
                'uz' => 'Haydalgan',
                'ru' => 'Вспахано',
                'en' => 'Plowed',
            ],
            self::COVER_CROP => [
                'uz' => 'Qoplama ekin ekildi',
                'ru' => 'Посажена покровная культура',
                'en' => 'Cover crop planted',
            ],
            self::MULCHED => [
                'uz' => 'Mulchalangan',
                'ru' => 'Замульчировано',
                'en' => 'Mulched',
            ],
            self::FERTILIZED => [
                'uz' => "O'g'itlangan",
                'ru' => 'Удобрено',
                'en' => 'Fertilized',
            ],
            self::PREPARED_FOR_NEXT => [
                'uz' => 'Keyingi mavsumga tayyorlangan',
                'ru' => 'Подготовлено к следующему сезону',
                'en' => 'Prepared for next season',
            ],
        };
    }

    /**
     * Get all statuses as options array for Filament.
     *
     * @return array<string, string>
     */
    public static function options(?string $locale = null): array
    {
        $locale = $locale ?? app()->getLocale();

        return collect(self::cases())
            ->mapWithKeys(fn (self $status) => [
                $status->value => $status->label()[$locale] ?? $status->label()['en'],
            ])
            ->all();
    }
}
