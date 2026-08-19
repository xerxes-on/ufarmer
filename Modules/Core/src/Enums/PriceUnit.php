<?php

declare(strict_types=1);

namespace Modules\Core\Enums;

enum PriceUnit: string
{
    case PerHour = 'per_hour';
    case PerKm = 'per_km';
    case PerAr = 'per_ar';
    case PerM2 = 'per_m2';
    case PerItem = 'per_item';
    case PerDay = 'per_day';
    case PerProject = 'per_project';

    public function label(): string
    {
        return match ($this) {
            self::PerHour => '/час',
            self::PerKm => '/км',
            self::PerAr => '/ар',
            self::PerM2 => '/м²',
            self::PerItem => '/шт',
            self::PerDay => '/день',
            self::PerProject => '/проект',
        };
    }

    /**
     * @return array<string, array{uz: string, ru: string, en: string}>
     */
    public static function translatedLabels(): array
    {
        return [
            self::PerHour->value => ['uz' => '/soat', 'ru' => '/час', 'en' => '/hour'],
            self::PerKm->value => ['uz' => '/km', 'ru' => '/км', 'en' => '/km'],
            self::PerAr->value => ['uz' => '/ar', 'ru' => '/ар', 'en' => '/ar'],
            self::PerM2->value => ['uz' => '/m²', 'ru' => '/м²', 'en' => '/m²'],
            self::PerItem->value => ['uz' => '/dona', 'ru' => '/шт', 'en' => '/item'],
            self::PerDay->value => ['uz' => '/kun', 'ru' => '/день', 'en' => '/day'],
            self::PerProject->value => ['uz' => '/loyiha', 'ru' => '/проект', 'en' => '/project'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::PerHour->value => self::PerHour->label(),
            self::PerKm->value => self::PerKm->label(),
            self::PerAr->value => self::PerAr->label(),
            self::PerM2->value => self::PerM2->label(),
            self::PerItem->value => self::PerItem->label(),
            self::PerDay->value => self::PerDay->label(),
            self::PerProject->value => self::PerProject->label(),
        ];
    }

    public function translatedLabel(?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();
        $labels = self::translatedLabels()[$this->value] ?? [];

        return $labels[$locale] ?? $labels['en'] ?? $this->label();
    }
}
