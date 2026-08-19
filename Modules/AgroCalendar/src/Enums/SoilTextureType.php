<?php

declare(strict_types=1);

namespace Modules\AgroCalendar\Enums;

enum SoilTextureType: string
{
    case SANDY = 'sandy';
    case LOAMY_SAND = 'loamy_sand';
    case SANDY_LOAM = 'sandy_loam';
    case LOAM = 'loam';
    case SILT_LOAM = 'silt_loam';
    case SILT = 'silt';
    case SANDY_CLAY_LOAM = 'sandy_clay_loam';
    case CLAY_LOAM = 'clay_loam';
    case SILTY_CLAY_LOAM = 'silty_clay_loam';
    case SANDY_CLAY = 'sandy_clay';
    case SILTY_CLAY = 'silty_clay';
    case CLAY = 'clay';

    public function label(): array
    {
        return match ($this) {
            self::SANDY => [
                'uz' => 'Qumli',
                'ru' => 'Песчаная',
                'en' => 'Sandy',
            ],
            self::LOAMY_SAND => [
                'uz' => 'Qumli-tuproqli',
                'ru' => 'Супесчаная',
                'en' => 'Loamy sand',
            ],
            self::SANDY_LOAM => [
                'uz' => 'Qumloq',
                'ru' => 'Песчаный суглинок',
                'en' => 'Sandy loam',
            ],
            self::LOAM => [
                'uz' => 'Tuproqli',
                'ru' => 'Суглинок',
                'en' => 'Loam',
            ],
            self::SILT_LOAM => [
                'uz' => 'Loyqali tuproq',
                'ru' => 'Пылеватый суглинок',
                'en' => 'Silt loam',
            ],
            self::SILT => [
                'uz' => 'Loyqa',
                'ru' => 'Пылеватая',
                'en' => 'Silt',
            ],
            self::SANDY_CLAY_LOAM => [
                'uz' => 'Qumli-giltuproq',
                'ru' => 'Песчано-глинистый суглинок',
                'en' => 'Sandy clay loam',
            ],
            self::CLAY_LOAM => [
                'uz' => 'Gilli tuproq',
                'ru' => 'Глинистый суглинок',
                'en' => 'Clay loam',
            ],
            self::SILTY_CLAY_LOAM => [
                'uz' => 'Loyqali-gilli tuproq',
                'ru' => 'Пылевато-глинистый суглинок',
                'en' => 'Silty clay loam',
            ],
            self::SANDY_CLAY => [
                'uz' => 'Qumli-gil',
                'ru' => 'Песчаная глина',
                'en' => 'Sandy clay',
            ],
            self::SILTY_CLAY => [
                'uz' => 'Loyqali-gil',
                'ru' => 'Пылеватая глина',
                'en' => 'Silty clay',
            ],
            self::CLAY => [
                'uz' => 'Gil',
                'ru' => 'Глина',
                'en' => 'Clay',
            ],
        };
    }

    /**
     * Get water holding capacity in mm/m
     */
    public function waterHoldingCapacity(): float
    {
        return match ($this) {
            self::SANDY => 50.0,
            self::LOAMY_SAND => 70.0,
            self::SANDY_LOAM => 100.0,
            self::LOAM => 150.0,
            self::SILT_LOAM => 180.0,
            self::SILT => 170.0,
            self::SANDY_CLAY_LOAM => 140.0,
            self::CLAY_LOAM => 160.0,
            self::SILTY_CLAY_LOAM => 170.0,
            self::SANDY_CLAY => 130.0,
            self::SILTY_CLAY => 150.0,
            self::CLAY => 140.0,
        };
    }
}
