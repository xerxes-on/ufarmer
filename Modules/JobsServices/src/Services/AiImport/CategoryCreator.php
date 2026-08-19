<?php

declare(strict_types=1);

namespace Modules\JobsServices\Services\AiImport;

use Illuminate\Support\Facades\Log;
use Modules\JobsServices\Models\AiImportAlias;
use Modules\JobsServices\Models\ServiceCategory;
use Modules\JobsServices\Support\OfferLocales;
use Throwable;

/**
 * Creates the service categories a sheet needs but the catalog lacks
 * (UFARM-2671).
 *
 * Every import of the real onboarding sheet stalled on the same wall: three of
 * its four service blocks — irrigation, greenhouses, spare parts — had no
 * category to attach to, so 42 of 46 correctly extracted workers could not be
 * published. Nothing was wrong with the extraction; the catalog simply did not
 * describe the business yet. Waiting for someone to hand-type three categories
 * in three languages made every import a two-person job.
 *
 * Three properties keep this safe:
 *
 *  - **Created inactive.** A category invented from a spreadsheet is a
 *    suggestion, not a decision. `is_active = false` keeps it out of the app
 *    and out of the resolver's candidate set until an admin activates it, so a
 *    bad guess is a row to delete rather than something customers see.
 *  - **Only after every deterministic and AI match has failed.** This runs last,
 *    so it can never shadow a category that already exists under another
 *    spelling — and it folds its own proposed names against the catalog before
 *    inserting, so a near-duplicate is reused rather than created.
 *  - **Nothing is cached.** The category itself is the durable record: it is a
 *    real catalog row, so exact matching finds it on the next import for free.
 *    Writing an alias as well would only re-create the trap that made a wrong
 *    guess permanent (UFARM-2671).
 *
 * A failure here is logged and swallowed. Naming is an improvement on an import
 * that already succeeded; it must never fail one.
 */
final class CategoryCreator
{
    public function __construct(
        private readonly AiExtractionClient $client = new AiExtractionClient,
    ) {}

    /**
     * Name and create a category for each label, returning label => new id.
     *
     * @param  array<string, string>  $labels  source label => the label again
     * @return array{created: array<string, int>, usage: array<string, mixed>}
     */
    public function create(array $labels): array
    {
        $empty = ['created' => [], 'usage' => []];

        if ($labels === [] || ! self::enabled()) {
            return $empty;
        }

        // Bounded for the same reason the resolver is: cost must not scale with
        // how badly a sheet is written.
        $max = (int) (config('services.openrouter.max_created_categories') ?: 10);
        $names = array_slice(array_values($labels), 0, $max);

        if (count($labels) > $max) {
            Log::info('AI import created fewer categories than were unmatched', [
                'unmatched' => count($labels),
                'creating' => $max,
            ]);
        }

        try {
            $response = $this->client->structured(
                CategoryNamingPrompt::messages($names),
                CategoryNamingSchema::responseFormat(),
            );
        } catch (Throwable $e) {
            Log::warning('AI category naming failed; leaving the rows unmatched', [
                'message' => $e->getMessage(),
            ]);

            return $empty;
        }

        return [
            'created' => $this->persist($response['content'], $names),
            'usage' => [
                'prompt_tokens' => $response['prompt_tokens'],
                'completion_tokens' => $response['completion_tokens'],
                'cost' => $response['cost'],
            ],
        ];
    }

    /**
     * Whether creating categories is switched on.
     *
     * Reads the key rather than leaning on config()'s default, because that
     * default only applies when the key is ABSENT. A server running a cached
     * config built before this feature existed has `services.openrouter` as a
     * populated array without this key, so the lookup returns null and
     * `(bool) null` silently disables the feature — which is exactly how this
     * shipped to dev and did nothing (UFARM-2671). Only an explicit false
     * turns it off.
     */
    private static function enabled(): bool
    {
        return config('services.openrouter.category_creation') !== false;
    }

    /**
     * @param  array<string, mixed>  $content
     * @param  array<int, string>  $requested
     * @return array<string, int>
     */
    private function persist(array $content, array $requested): array
    {
        $entries = $content[CategoryNamingSchema::COLLECTION_KEY] ?? null;

        if (! is_array($entries)) {
            return [];
        }

        // Only labels we actually asked about: the model echoing something else
        // back would otherwise create a category nothing in the sheet needs.
        $allowed = array_flip($requested);
        $created = [];

        foreach ($entries as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            $source = $entry['source'] ?? null;

            if (! is_string($source) || ! isset($allowed[$source])) {
                Log::warning('AI category naming returned a label that was not requested', [
                    'source' => is_string($source) ? $source : gettype($source),
                ]);

                continue;
            }

            $names = $this->names($entry);

            if ($names === null) {
                continue;
            }

            $category = $this->store($source, $names);

            if ($category !== null) {
                $created[$source] = $category;
            }
        }

        return $created;
    }

    /**
     * @param  array<string, mixed>  $entry
     * @return array<string, string>|null
     */
    private function names(array $entry): ?array
    {
        $names = [];

        foreach (OfferLocales::all() as $locale) {
            $value = FieldParser::text($entry[$locale] ?? null, 255);

            if ($value === null) {
                // A category missing a translation never matches a sheet
                // written in that language — the exact gap this closes.
                return null;
            }

            $names[$locale] = $value;
        }

        return $names;
    }

    /**
     * @param  array<string, string>  $names
     */
    private function store(string $source, array $names): ?int
    {
        try {
            $existing = $this->existing($names);

            if ($existing !== null) {
                // The model named something the catalog already has under a
                // spelling the resolver missed. Reuse it — a second row for
                // the same service would be the worse outcome.
                return $existing;
            }

            $category = ServiceCategory::create([
                'name' => $names,
                'applies_to' => 'both',
                // Inactive on purpose — see the class docblock. Also why
                // sort_order needs no care: the unique index covers active
                // rows only.
                'is_active' => false,
                'sort_order' => 0,
            ]);

            $id = (int) $category->getKey();

            Log::info('AI import created a service category for review', [
                'source' => $source,
                'category_id' => $id,
                'name' => $names,
            ]);

            return $id;
        } catch (Throwable $e) {
            Log::warning('AI import could not create a service category', [
                'source' => $source,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * A category already holding one of these names, under any locale and
     * regardless of whether it is active.
     *
     * Checked against the folded form rather than the literal string, so
     * "Ehtiyot qismlar" finds an existing "ehtiyot qismlari" — the resolver
     * would have matched it if the sheet had spelled it that way, and this
     * must not create a near-duplicate it missed.
     *
     * @param  array<string, string>  $names
     */
    private function existing(array $names): ?int
    {
        $wanted = array_filter(array_map(
            static fn (string $name): string => AiImportAlias::normalize($name),
            $names,
        ));

        if ($wanted === []) {
            return null;
        }

        foreach (ServiceCategory::query()->get() as $category) {
            foreach ($category->getTranslations('name') as $translation) {
                if (! is_string($translation)) {
                    continue;
                }

                if (in_array(AiImportAlias::normalize($translation), $wanted, true)) {
                    return (int) $category->getKey();
                }
            }
        }

        return null;
    }
}
