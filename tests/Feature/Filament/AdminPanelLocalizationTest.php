<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Traits\HasTranslatedFilamentLabels;
use Filament\Facades\Filament;
use Modules\Agronom\Enums\ServiceRequestStatus;
use Modules\Agronom\Enums\ServiceRequestType;
use ReflectionMethod;
use Tests\TestCase;

final class AdminPanelLocalizationTest extends TestCase
{
    public function test_every_admin_resource_resolves_labels_in_every_locale(): void
    {
        $panel = Filament::getPanel('admin');
        Filament::setCurrentPanel($panel);

        foreach (['en', 'ru', 'uz'] as $locale) {
            app()->setLocale($locale);

            foreach ($panel->getResources() as $resource) {
                foreach (['getNavigationLabel', 'getModelLabel', 'getPluralModelLabel'] as $method) {
                    $label = (string) $resource::$method();

                    $this->assertNotSame('', $label, "$resource::$method returned an empty label for $locale.");
                    $this->assertStringNotContainsString('::', $label, "$resource::$method exposed a translation key for $locale.");
                    $this->assertStringNotContainsString('admin-panel.', $label, "$resource::$method exposed a translation key for $locale.");
                }
            }
        }
    }

    public function test_every_admin_page_resolves_its_navigation_label_in_every_locale(): void
    {
        $panel = Filament::getPanel('admin');
        Filament::setCurrentPanel($panel);

        foreach (['en', 'ru', 'uz'] as $locale) {
            app()->setLocale($locale);

            foreach ($panel->getPages() as $page) {
                $label = (string) $page::getNavigationLabel();

                $this->assertNotSame('', $label, "$page returned an empty navigation label for $locale.");
                $this->assertStringNotContainsString('::', $label, "$page exposed a translation key for $locale.");
            }
        }
    }

    public function test_every_trait_resource_has_complete_label_translations(): void
    {
        $panel = Filament::getPanel('admin');
        Filament::setCurrentPanel($panel);

        foreach ($panel->getResources() as $resource) {
            if (! in_array(HasTranslatedFilamentLabels::class, class_uses_recursive($resource), true)) {
                continue;
            }

            $method = new ReflectionMethod($resource, 'translationKey');
            $method->setAccessible(true);
            $key = $method->invoke(null);

            foreach (['en', 'ru', 'uz'] as $locale) {
                $translations = require resource_path("lang/$locale/admin-panel.php");
                $labels = $translations['resource_labels'][$key] ?? $translations['resources'][$key] ?? null;

                $this->assertIsArray($labels, "$locale is missing labels for $resource.");
                $this->assertArrayHasKey('navigation_label', $labels, "$locale is missing the navigation label for $resource.");
                $this->assertArrayHasKey('model_label', $labels, "$locale is missing the model label for $resource.");
                $this->assertArrayHasKey('plural_model_label', $labels, "$locale is missing the plural model label for $resource.");
            }
        }
    }

    public function test_shared_translation_catalogs_have_matching_keys(): void
    {
        $english = require resource_path('lang/en/admin-panel.php');

        foreach (['ru', 'uz'] as $locale) {
            $translations = require resource_path("lang/$locale/admin-panel.php");

            $this->assertSame(array_keys($english['resource_labels']), array_keys($translations['resource_labels']));
            $this->assertSame(array_keys($english['relation_labels']), array_keys($translations['relation_labels']));
            $this->assertSame(array_keys($english['pages']), array_keys($translations['pages']));
            $this->assertSame(
                array_keys($english['pages']['agronom_settings']),
                array_keys($translations['pages']['agronom_settings']),
            );
            $this->assertSame(
                array_keys($english['resources']['service_request']['statuses']),
                array_keys($translations['resources']['service_request']['statuses']),
            );
            $this->assertSame(
                array_keys($english['resources']['service_request']['types']),
                array_keys($translations['resources']['service_request']['types']),
            );
        }
    }

    public function test_navigation_labels_remain_unique_in_every_locale(): void
    {
        $panel = Filament::getPanel('admin');
        Filament::setCurrentPanel($panel);

        foreach (['en', 'ru', 'uz'] as $locale) {
            app()->setLocale($locale);
            $labels = [];

            foreach ($panel->getResources() as $resource) {
                $labels[(string) $resource::getNavigationGroup()][] = (string) $resource::getNavigationLabel();
            }

            foreach ($labels as $group => $groupLabels) {
                $duplicates = array_keys(array_filter(array_count_values($groupLabels), fn (int $count): bool => $count > 1));

                $this->assertSame([], $duplicates, "$locale has duplicate labels in $group.");
            }
        }
    }

    public function test_service_request_statuses_and_types_use_the_active_locale(): void
    {
        $expectations = [
            'en' => ['Pending', 'In Person'],
            'ru' => ['Ожидает', 'Личная встреча'],
            'uz' => ['Kutilmoqda', 'Shaxsan uchrashuv'],
        ];

        foreach ($expectations as $locale => [$status, $type]) {
            app()->setLocale($locale);

            $this->assertSame($status, ServiceRequestStatus::PENDING->label());
            $this->assertSame($type, ServiceRequestType::InPerson->label());
        }
    }
}
