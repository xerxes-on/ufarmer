<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\NavigationGroup;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Tests\TestCase;

/**
 * Locks the consolidated sidebar in place (UFARM-2669).
 *
 * The panel had drifted to 33 groups for 67 resources because a group was just
 * a string literal repeated per resource -- and could be set from three
 * different places, the last of which silently outranked the others. These
 * tests fail on the reintroduction of any of those routes.
 */
class NavigationGroupingTest extends TestCase
{
    /** @return array<int, class-string> */
    private function adminResources(): array
    {
        $panel = Filament::getPanel('admin');
        Filament::setCurrentPanel($panel);

        return $panel->getResources();
    }

    public function test_every_admin_resource_belongs_to_a_declared_group(): void
    {
        $declared = array_map(
            static fn (NavigationGroup $g): string => $g->value,
            NavigationGroup::cases()
        );

        $stray = [];
        foreach ($this->adminResources() as $resource) {
            $group = (string) $resource::getNavigationGroup();

            if (! in_array($group, $declared, true)) {
                $stray[$resource] = $group;
            }
        }

        $this->assertSame([], $stray, 'Resources outside the declared groups: '.json_encode($stray));
    }

    public function test_the_panel_registers_every_declared_group(): void
    {
        $panel = Filament::getPanel('admin');
        Filament::setCurrentPanel($panel);

        $this->assertSame(
            array_map(static fn (NavigationGroup $g): string => $g->value, NavigationGroup::cases()),
            array_keys($panel->getNavigationGroups()),
            'Panel::navigationGroups() must be keyed by every enum value, in enum order.'
        );
    }

    public function test_no_declared_group_is_empty(): void
    {
        $counts = array_fill_keys(
            array_map(static fn (NavigationGroup $g): string => $g->value, NavigationGroup::cases()),
            0
        );

        foreach ($this->adminResources() as $resource) {
            $group = (string) $resource::getNavigationGroup();

            if (isset($counts[$group])) {
                $counts[$group]++;
            }
        }

        $this->assertSame([], array_keys(array_filter($counts, static fn (int $n): bool => $n === 0)));
    }

    public function test_navigation_labels_are_unique_within_a_group(): void
    {
        $seen = [];
        foreach ($this->adminResources() as $resource) {
            $seen[(string) $resource::getNavigationGroup()][] = (string) $resource::getNavigationLabel();
        }

        foreach ($seen as $group => $labels) {
            $duplicates = array_keys(array_filter(array_count_values($labels), static fn (int $n): bool => $n > 1));

            $this->assertSame([], $duplicates, "Duplicate labels in \"$group\": ".implode(', ', $duplicates));
        }
    }

    /**
     * The per-resource `navigation_group` lang key used to win over the
     * $navigationGroup property, which is how one module's resources ended up
     * scattered across five groups.
     */
    public function test_resource_lang_files_do_not_override_the_navigation_group(): void
    {
        foreach (['en', 'ru', 'uz'] as $locale) {
            $path = resource_path("lang/$locale/admin-panel.php");
            $resources = require $path;

            foreach ($resources['resources'] ?? [] as $key => $labels) {
                $this->assertArrayNotHasKey(
                    'navigation_group',
                    $labels,
                    "$locale/admin-panel.php: resources.$key must not set navigation_group; "
                    .'groups belong to App\Filament\NavigationGroup.'
                );
            }
        }
    }

    public function test_every_group_has_a_label_in_every_locale(): void
    {
        foreach (['en', 'ru', 'uz'] as $locale) {
            $translations = require resource_path("lang/$locale/admin-panel.php");

            foreach (NavigationGroup::cases() as $group) {
                $this->assertArrayHasKey(
                    $group->name,
                    $translations['navigation_groups'] ?? [],
                    "$locale/admin-panel.php is missing navigation_groups.{$group->name}"
                );
            }
        }
    }
}
