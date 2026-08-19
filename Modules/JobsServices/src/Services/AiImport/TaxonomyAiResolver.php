<?php

declare(strict_types=1);

namespace Modules\JobsServices\Services\AiImport;

use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Second, cheap AI pass that maps leftover names onto catalog ids (UFARM-2644).
 *
 * Deterministic matching gets most of the way — see TaxonomyResolver — but it
 * cannot bridge genuine cross-language differences: "Услуги Дрона" and
 * "Purkash" are the same service, and no amount of string folding will say so.
 * That judgement is what an LLM is actually good at.
 *
 * Three properties make this safe and cheap:
 *
 *  - **The returned id is validated against the exact candidate set that was
 *    sent** — not merely "does this id exist". Active, correct-type and
 *    in-scope are then true by construction, and a hallucinated id that
 *    happens to be a real row still fails.
 *  - It runs over *distinct unresolved names*, so cost is independent of row
 *    count and it is skipped entirely when everything already matched.
 *  - **Its answers are not remembered.** They used to be written to the alias
 *    table, which outranks every matching pass but an exact catalog hit — so a
 *    single wrong guess became a permanent, invisible fact for every later
 *    import (UFARM-2671). A guess is re-made and re-checked each time; only a
 *    human's correction is durable.
 *
 * A failure here is logged and swallowed. Taxonomy resolution is a
 * convenience; it must never fail an extraction that has already been paid for.
 */
final class TaxonomyAiResolver
{
    public function __construct(
        private readonly AiExtractionClient $client = new AiExtractionClient,
    ) {}

    /**
     * @param  array<string, string>  $unresolved  source name => the name again (deduped by caller)
     * @param  array<int, array{id: int, label: string}>  $candidates
     * @return array{resolved: array<string, int>, usage: array<string, mixed>}
     */
    public function resolve(string $taxonomy, array $unresolved, array $candidates): array
    {
        $empty = ['resolved' => [], 'usage' => []];

        if ($unresolved === [] || $candidates === []) {
            return $empty;
        }

        if (! (bool) config('services.openrouter.taxonomy_resolution', true)) {
            return $empty;
        }

        $maxNames = (int) config('services.openrouter.max_resolution_names', 40);
        $maxCandidates = (int) config('services.openrouter.max_resolution_categories', 200);

        $names = array_slice(array_values($unresolved), 0, $maxNames);
        $candidates = array_slice($candidates, 0, $maxCandidates);

        if (count($unresolved) > $maxNames || count($candidates) > $maxCandidates) {
            Log::info('AI taxonomy resolution truncated its inputs', [
                'taxonomy' => $taxonomy,
                'names' => count($unresolved),
                'names_sent' => count($names),
                'candidates_available' => count($candidates),
                'candidates_sent' => count($candidates),
            ]);
        }

        // Keyed by id so validation is an array lookup against exactly what
        // was offered — the property this whole class rests on.
        $allowed = [];

        foreach ($candidates as $candidate) {
            $allowed[(int) $candidate['id']] = $candidate['label'];
        }

        try {
            $response = $this->client->structured(
                TaxonomyMatchPrompt::messages($taxonomy, $names, $allowed),
                TaxonomyMatchSchema::responseFormat(),
            );
        } catch (Throwable $e) {
            Log::warning('AI taxonomy resolution failed; falling back to unresolved', [
                'taxonomy' => $taxonomy,
                'message' => $e->getMessage(),
            ]);

            return $empty;
        }

        return [
            'resolved' => $this->accept($taxonomy, $response['content'], $allowed),
            'usage' => [
                'prompt_tokens' => $response['prompt_tokens'],
                'completion_tokens' => $response['completion_tokens'],
                'cost' => $response['cost'],
            ],
        ];
    }

    /**
     * Keep only matches that name an id from the candidate set and clear the
     * confidence bar. Everything else is dropped and logged — a null is always
     * better than a wrong id, because a wrong one is invisible to the admin
     * reviewing the import.
     *
     * @param  array<string, mixed>  $content
     * @param  array<int, string>  $allowed
     * @return array<string, int>
     */
    private function accept(string $taxonomy, array $content, array $allowed): array
    {
        $threshold = (float) config('services.openrouter.category_match_threshold', 0.8);
        $matches = $content['matches'] ?? null;

        if (! is_array($matches)) {
            return [];
        }

        $resolved = [];

        foreach ($matches as $match) {
            if (! is_array($match)) {
                continue;
            }

            $source = $match['source_name'] ?? null;
            $id = $match['target_id'] ?? null;
            $confidence = $match['confidence'] ?? null;

            if (! is_string($source) || $source === '' || $id === null) {
                continue;
            }

            if (! is_int($id) || ! array_key_exists($id, $allowed)) {
                Log::warning('AI taxonomy resolution returned an id outside the candidate set', [
                    'taxonomy' => $taxonomy,
                    'source_name' => $source,
                    'target_id' => $id,
                ]);

                continue;
            }

            if (! is_numeric($confidence) || (float) $confidence < $threshold) {
                continue;
            }

            $resolved[$source] = $id;
        }

        return $resolved;
    }
}
