<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;

/**
 * Per-resource label overrides read from the panel's lang files.
 *
 * Deliberately does NOT cover the navigation group: groups are a panel-wide
 * concern owned by App\Filament\NavigationGroup, which carries its own
 * translations under `admin-panel.navigation_groups`. A per-resource
 * `navigation_group` key here used to silently outrank the resource's
 * $navigationGroup property, which is how the sidebar accumulated 33 groups
 * (UFARM-2669).
 */
trait HasTranslatedFilamentLabels
{
    public static function getNavigationLabel(): string
    {
        return self::translatedLabel('navigation_label') ?? parent::getNavigationLabel();
    }

    public static function getModelLabel(): string
    {
        return self::translatedLabel('model_label') ?? parent::getModelLabel();
    }

    public static function getPluralModelLabel(): string
    {
        return self::translatedLabel('plural_model_label') ?? parent::getPluralModelLabel();
    }

    /**
     * Translation-file key for this resource under `resources.<key>` in
     * resources/lang/{locale}/<translationFile()>.php, e.g. 'employee' or
     * 'marketplace_proposal'.
     */
    protected static function translationKey(): string
    {
        return Str::of(class_basename(static::class))
            ->beforeLast('Resource')
            ->snake()
            ->toString();
    }

    /**
     * Lang-file basename (without extension) this resource's labels live
     * in. Defaults to 'admin-panel' — the default Filament panel's lang
     * file. Resources served by other panels (e.g. uzgidromet) can
     * override this to point at their own file.
     */
    protected static function translationFile(): string
    {
        return 'admin-panel';
    }

    private static function translatedLabel(string $suffix): ?string
    {
        $key = static::translationFile().'.resource_labels.'.static::translationKey().'.'.$suffix;

        if (Lang::has($key)) {
            return __($key);
        }

        $key = static::translationFile().'.resources.'.static::translationKey().'.'.$suffix;

        return Lang::has($key) ? __($key) : null;
    }
}
