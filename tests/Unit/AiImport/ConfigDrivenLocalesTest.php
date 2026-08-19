<?php

declare(strict_types=1);

namespace Tests\Unit\AiImport;

use Modules\JobsServices\Services\AiImport\CategoryNamingPrompt;
use Modules\JobsServices\Services\AiImport\CategoryNamingSchema;
use Modules\JobsServices\Services\AiImport\Worker\WorkerExtractionSchema;
use Tests\TestCase;

class ConfigDrivenLocalesTest extends TestCase
{
    public function test_import_schemas_expand_for_a_new_configured_locale(): void
    {
        config(['app.api_locales' => ['uz', 'ru', 'en', 'kk']]);

        $workerLanguage = data_get(
            WorkerExtractionSchema::responseFormat(),
            'json_schema.schema.properties.workers.items.properties.language.enum',
        );
        $categoryItem = data_get(
            CategoryNamingSchema::responseFormat(),
            'json_schema.schema.properties.categories.items',
        );
        $systemPrompt = CategoryNamingPrompt::messages(['Irrigation'])[0]['content'];

        $this->assertSame(['uz', 'ru', 'en', 'kk', null], $workerLanguage);
        $this->assertContains('kk', $categoryItem['required']);
        $this->assertArrayHasKey('kk', $categoryItem['properties']);
        $this->assertStringContainsString('uz, ru, en, kk', $systemPrompt);
    }
}
