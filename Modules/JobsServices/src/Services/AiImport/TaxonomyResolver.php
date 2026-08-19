<?php

declare(strict_types=1);

namespace Modules\JobsServices\Services\AiImport;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Modules\JobsServices\Models\AiImportAlias;

/**
 * Resolves free-text names onto real catalog ids (UFARM-2644).
 *
 * The rule this class exists to enforce: **the model never picks a foreign
 * key**. It returns the spelling it read; matching that spelling to a row is
 * done here, against the live catalog, so a hallucinated id cannot reach the
 * database.
 *
 * Matching runs in five passes, cheapest first:
 *   1. exact, on the folded form (see AiImportAlias::normalize)
 *   2. the learned alias table — a previous AI or admin decision
 *   3. suffix-stripped, applied to BOTH sides
 *   4. suffix-stripped, disambiguated by the kind of place the suffix denotes
 *   5. unique prefix containment
 *
 * Passes 3 and 4 are what make real data work. The catalog stores "Toshkent
 * shahri" and "Toshkent viloyati"; the sheet writes "Тошкент ш". Stripping only
 * the input would never reach the catalog entry, so the index is stripped too —
 * but that leaves the stem "toshkent" claimed by two different regions. Pass 4
 * splits them on the very suffix pass 3 removed, since "ш" means the city and
 * "вилояти" the province. Together they take the live region list from 5/14
 * exact matches to 14/14.
 *
 * Anything still unresolved is left for the AI resolver, and whatever it
 * decides is written back to the alias table so the cost is paid once.
 */
final class TaxonomyResolver
{
    /**
     * Administrative and catalog suffixes that vary between how a sheet writes
     * a name and how the catalog stores it. Longest first: "viloyati" must be
     * tried before "vilo", and "shahri" before "sh".
     *
     * @var array<int, string>
     */
    private const SUFFIXES = [
        'respublikasi', 'viloyati', 'vilojati', 'viloyat', 'shahri', 'shahar',
        'oblast', 'region', 'tumani', 'rajon', 'tuman', 'city', 'obl', 'sh',
    ];

    /**
     * Suffixes grouped by the kind of place they denote.
     *
     * "Тошкент ш" and "Тошкент вилояти" share the stem "toshkent" but are two
     * different regions — Tashkent the city and Tashkent the province. Their
     * suffixes are exactly what tells them apart, so an ambiguous stem is
     * re-resolved by comparing the input's kind against each candidate's.
     *
     * @var array<string, array<int, string>>
     */
    private const SUFFIX_KINDS = [
        'city' => ['shahri', 'shahar', 'city', 'sh'],
        'province' => ['viloyati', 'vilojati', 'viloyat', 'oblast', 'region', 'obl'],
        'district' => ['tumani', 'tuman', 'rajon'],
        'republic' => ['respublikasi'],
    ];

    /**
     * A stripped key must keep at least this many characters, so "sh" itself
     * never collapses to nothing and short names stay distinguishable.
     */
    private const MIN_STEM = 3;

    /** @var array<string, array<string, int>> taxonomy => normalized => id */
    private array $exact = [];

    /** @var array<string, array<string, int|null>> taxonomy => stem => id (null marks ambiguity) */
    private array $stems = [];

    /** @var array<string, array<string, array<string, int>>> taxonomy => stem => kind => id */
    private array $stemsByKind = [];

    /**
     * Index a catalog for matching.
     *
     * Every translation of every row is indexed, because the same sheet mixes
     * Uzbek, Russian and English spellings freely.
     *
     * @param  Collection<int, Model>  $records
     * @param  callable(Model): array<string, string>  $translations
     */
    public function index(string $taxonomy, Collection $records, callable $translations): void
    {
        $this->exact[$taxonomy] ??= [];
        $this->stems[$taxonomy] ??= [];
        $this->stemsByKind[$taxonomy] ??= [];

        foreach ($records as $record) {
            $id = (int) $record->getKey();

            foreach ($translations($record) as $name) {
                $normalized = AiImportAlias::normalize($name);

                if ($normalized === '') {
                    continue;
                }

                $this->exact[$taxonomy][$normalized] ??= $id;

                $stem = $this->stem($normalized);

                if ($stem === '') {
                    continue;
                }

                // Remember which kind of place this spelling denotes, so an
                // ambiguous stem can still be split by suffix later.
                $kind = $this->kindOf($normalized);

                if ($kind !== null) {
                    $this->stemsByKind[$taxonomy][$stem][$kind] ??= $id;
                }

                // A stem claimed by two different rows is not usable — better
                // no match than a coin flip between two regions.
                if (array_key_exists($stem, $this->stems[$taxonomy])
                    && $this->stems[$taxonomy][$stem] !== $id) {
                    $this->stems[$taxonomy][$stem] = null;

                    continue;
                }

                $this->stems[$taxonomy][$stem] ??= $id;
            }
        }
    }

    /**
     * @param  array<int, int>|null  $allowedIds  restrict to this subset (e.g. cities of one region)
     * @return array{id: int|null, matched_by: string|null}
     */
    public function resolve(string $taxonomy, ?string $name, ?array $allowedIds = null): array
    {
        $normalized = AiImportAlias::normalize($name);

        if ($normalized === '') {
            return ['id' => null, 'matched_by' => null];
        }

        $exact = $this->exact[$taxonomy][$normalized] ?? null;

        if ($this->permitted($exact, $allowedIds)) {
            return ['id' => $exact, 'matched_by' => 'exact'];
        }

        $alias = AiImportAlias::lookup($taxonomy, $name);

        if ($this->permitted($alias, $allowedIds)) {
            return ['id' => $alias, 'matched_by' => 'alias'];
        }

        $stem = $this->stem($normalized);
        $byStem = $stem === '' ? null : ($this->stems[$taxonomy][$stem] ?? null);

        if ($this->permitted($byStem, $allowedIds)) {
            return ['id' => $byStem, 'matched_by' => 'exact'];
        }

        // The stem is shared by several rows ("toshkent" is both the city and
        // the province). The input's own suffix says which one it meant.
        if ($stem !== '' && $byStem === null) {
            $kind = $this->kindOf($normalized);
            $byKind = $kind === null ? null : ($this->stemsByKind[$taxonomy][$stem][$kind] ?? null);

            if ($this->permitted($byKind, $allowedIds)) {
                return ['id' => $byKind, 'matched_by' => 'exact'];
            }
        }

        $byPrefix = $this->uniquePrefixMatch($taxonomy, $stem !== '' ? $stem : $normalized, $allowedIds);

        if ($byPrefix !== null) {
            return ['id' => $byPrefix, 'matched_by' => 'exact'];
        }

        return ['id' => null, 'matched_by' => null];
    }

    /**
     * Ids known to this taxonomy — the candidate set the AI resolver is
     * allowed to choose from, and validated against afterwards.
     *
     * @return array<int, int>
     */
    public function knownIds(string $taxonomy): array
    {
        return array_values(array_unique(array_values($this->exact[$taxonomy] ?? [])));
    }

    /**
     * Last deterministic attempt: one catalog stem contains the input, or vice
     * versa, and only one. Ambiguity yields null rather than a guess.
     *
     * @param  array<int, int>|null  $allowedIds
     */
    private function uniquePrefixMatch(string $taxonomy, string $needle, ?array $allowedIds): ?int
    {
        if (mb_strlen($needle) < 4) {
            return null;
        }

        $found = null;

        foreach ($this->stems[$taxonomy] ?? [] as $stem => $id) {
            if ($id === null || ! $this->permitted($id, $allowedIds)) {
                continue;
            }

            if (! str_starts_with($stem, $needle) && ! str_starts_with($needle, $stem)) {
                continue;
            }

            if ($found !== null && $found !== $id) {
                return null;
            }

            $found = $id;
        }

        return $found;
    }

    /**
     * @param  array<int, int>|null  $allowedIds
     */
    private function permitted(?int $id, ?array $allowedIds): bool
    {
        if ($id === null) {
            return false;
        }

        return $allowedIds === null || in_array($id, $allowedIds, true);
    }

    private function stem(string $normalized): string
    {
        foreach (self::SUFFIXES as $suffix) {
            if ($normalized === $suffix) {
                continue;
            }

            if (str_ends_with($normalized, $suffix)
                && mb_strlen($normalized) - mb_strlen($suffix) >= self::MIN_STEM) {
                return substr($normalized, 0, -strlen($suffix));
            }
        }

        return $normalized;
    }

    /**
     * Which kind of place a spelling denotes, read off its suffix — or null
     * when it carries none ("Самарканд" alone says nothing about city vs
     * province, and must not be forced into a guess).
     */
    private function kindOf(string $normalized): ?string
    {
        foreach (self::SUFFIXES as $suffix) {
            if ($normalized === $suffix) {
                continue;
            }

            if (! str_ends_with($normalized, $suffix)
                || mb_strlen($normalized) - mb_strlen($suffix) < self::MIN_STEM) {
                continue;
            }

            foreach (self::SUFFIX_KINDS as $kind => $suffixes) {
                if (in_array($suffix, $suffixes, true)) {
                    return $kind;
                }
            }

            return null;
        }

        return null;
    }
}
