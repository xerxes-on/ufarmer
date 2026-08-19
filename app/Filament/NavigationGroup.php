<?php

declare(strict_types=1);

namespace App\Filament;

use Filament\Navigation\NavigationGroup as FilamentNavigationGroup;
use Illuminate\Support\Facades\Lang;

/**
 * The panel's navigation groups, in sidebar order (UFARM-2669).
 *
 * Before this existed, 67 resources declared 33 groups as free-text string
 * literals -- some as a static property, some via a getNavigationGroup()
 * override, some through per-module or per-resource translation keys. That
 * produced an average of two items per group, five separate groups for one
 * module's six resources, and two distinct groups both rendering as "Service
 * Types". Anything a group was named in one place and mistyped in another
 * silently became a new group.
 *
 * Declaring the set here means the sidebar is enumerable, and the constant
 * order below is the order the panel renders (see AdminPanelProvider).
 */
enum NavigationGroup: string
{
    case Catalog = 'Catalog';
    case CropProtection = 'Crop Protection';
    case AgroCalculator = 'Agro Calculator';
    case ServicesAndJobs = 'Services & Jobs';
    case MarketplaceAndPrices = 'Marketplace & Prices';
    case Content = 'Content';
    case Diagnostics = 'Diagnostics';
    case Administration = 'Administration';

    /**
     * Sidebar label in the active locale, falling back to the English case
     * value when a locale has no entry yet.
     */
    public function label(): string
    {
        $key = 'admin-panel.navigation_groups.'.$this->name;

        return Lang::has($key) ? __($key) : $this->value;
    }

    /**
     * Filament group definitions for Panel::navigationGroups(), keyed by the
     * enum value. Resources declare the group as a static property, which PHP
     * requires to be a constant expression, so they can only reference
     * NavigationGroup::Case->value -- the untranslated string. Keying on that
     * value lets Filament match a resource to its group (NavigationManager
     * compares the array key as well as the label) while the sidebar still
     * renders the translated label.
     *
     * @return array<string, FilamentNavigationGroup>
     */
    public static function ordered(): array
    {
        $groups = [];

        foreach (self::cases() as $group) {
            $groups[$group->value] = FilamentNavigationGroup::make($group->label());
        }

        return $groups;
    }
}
