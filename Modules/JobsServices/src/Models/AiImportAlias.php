<?php

declare(strict_types=1);

namespace Modules\JobsServices\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * A learned "this spelling means that catalog id" mapping (UFARM-2644).
 *
 * Source spreadsheets name categories and regions in whatever mix of Uzbek,
 * Russian, Latin and Cyrillic the author had to hand. Once an admin resolves
 * one on the review screen it is remembered here, so the correction is never
 * re-typed.
 *
 * **Admin decisions only.** AI guesses were cached here too until an alias for
 * "Услуги Орошения" silently pinned ten irrigation workers to "Tractor
 * services" on every import after the one that guessed it — aliases outrank
 * every matching pass but an exact catalog hit, so a single wrong answer became
 * permanent and invisible. A guess is now re-made and re-checked each run, and
 * a category the catalog genuinely lacks is created rather than guessed at
 * (UFARM-2671).
 *
 * Keyed by `taxonomy` rather than anything worker-specific, so a future
 * importer resolving against a different catalog just uses a new value.
 */
class AiImportAlias extends Model
{
    public const TAXONOMY_SERVICE_CATEGORY = 'service_category';

    public const TAXONOMY_REGION = 'region';

    public const TAXONOMY_CITY = 'city';

    public const ORIGIN_AI = 'ai';

    public const ORIGIN_ADMIN = 'admin';

    protected $table = 'ai_import_aliases';

    protected $guarded = ['id'];

    protected $casts = [
        'target_id' => 'integer',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Cyrillic → Latin-Uzbek, applied before `Str::ascii()`.
     *
     * `Str::ascii()` alone is wrong for this data: it folds Cyrillic `ш` to
     * `s` while Latin Uzbek writes `sh`, so "Тошкент" and "Toshkent" would
     * never meet. Worse, it maps `ў` and `ҳ` — both common in Uzbek place
     * names — to the empty string, silently dropping letters.
     *
     * Multi-character outputs (`sh`, `ch`, `ya`) are chosen to match the Latin
     * spelling they fold onto. `strtr()` with an array always prefers the
     * longest matching key, so ordering here is presentational only.
     *
     * @var array<string, string>
     */
    private const CYRILLIC_TO_LATIN = [
        'щ' => 'sh', 'ш' => 'sh', 'ч' => 'ch', 'ц' => 'ts',
        'ю' => 'yu', 'я' => 'ya', 'ё' => 'yo', 'ж' => 'j',
        'ў' => 'o', 'қ' => 'k', 'ғ' => 'g', 'ҳ' => 'h', 'х' => 'h',
        'й' => 'y', 'ъ' => '', 'ь' => '',
        'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd',
        'е' => 'e', 'з' => 'z', 'и' => 'i', 'к' => 'k', 'л' => 'l',
        'м' => 'm', 'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r',
        'с' => 's', 'т' => 't', 'у' => 'u', 'ф' => 'f', 'ы' => 'i',
        'э' => 'e',
    ];

    /**
     * Latin letters whose Uzbek spelling is genuinely unstable, folded to one
     * representative after transliteration.
     *
     * Uzbek Latin distinguishes q/k and x/h where Russian-influenced Cyrillic
     * spellings do not: "Самарканд" vs "Samarqand", "Хоразм" vs "Xorazm",
     * "Жиззах" vs "Jizzax". Folding q→k and x→h on both sides makes those
     * pairs meet. Over-folding is safe here — the worst case is two genuinely
     * different names colliding, and every match is still confirmed against
     * the real catalog before anything is written.
     *
     * @var array<string, string>
     */
    private const LATIN_EQUIVALENTS = [
        'q' => 'k',
        'x' => 'h',
        'c' => 'k',
        'w' => 'v',
    ];

    /**
     * Fold a name to its comparable form.
     *
     * Transliterates Cyrillic to Latin Uzbek first (see above), lets
     * `Str::ascii()` handle anything left, folds the unstable Latin letters,
     * and finally drops everything that is not alphanumeric. Collapses
     * "Тошкент ш.", "Toshkent sh" and "тошкентш" onto one key, and likewise
     * "Самарканд" onto "Samarqand".
     *
     * Deliberately aggressive: a false match costs nothing, because every
     * resolved id is confirmed against the real catalog before it is written.
     * A missed match costs an AI call, or an admin's time on the review
     * screen.
     */
    public static function normalize(?string $value): string
    {
        $value = mb_strtolower(trim((string) $value));

        $value = strtr($value, self::CYRILLIC_TO_LATIN);

        $value = mb_strtolower(Str::ascii($value));

        $value = strtr($value, self::LATIN_EQUIVALENTS);

        return (string) preg_replace('/[^a-z0-9]+/', '', $value);
    }

    /**
     * Resolve a source spelling to a catalog id, or null when unseen.
     *
     * Admin mappings only. A model guess used to be cached here too, which
     * turned one bad guess into a permanent fact: aliases outrank every
     * matching pass except an exact catalog hit, so "Услуги Орошения"
     * resolved to "Tractor services" on every subsequent import, invisibly,
     * because the first import had guessed it once (UFARM-2671).
     *
     * There is nothing left for an AI alias to buy. A category the catalog
     * lacks is now created rather than guessed at, and a created category is a
     * real catalog row that exact matching finds for free next time. Caching
     * the guess only preserved the mistakes.
     */
    public static function lookup(string $taxonomy, ?string $source): ?int
    {
        $norm = self::normalize($source);

        if ($norm === '') {
            return null;
        }

        return self::query()
            ->where('taxonomy', $taxonomy)
            ->where('source_norm', $norm)
            ->where('origin', self::ORIGIN_ADMIN)
            ->value('target_id');
    }

    /**
     * Remember a mapping an admin decided.
     *
     * `origin` remains a column because the table still holds AI rows written
     * before guesses stopped being cached; `lookup()` ignores those, and a
     * later admin correction upgrades the row in place rather than needing it
     * deleted first. Nothing writes ORIGIN_AI any more.
     */
    public static function remember(
        string $taxonomy,
        ?string $source,
        int $targetId,
        string $origin = self::ORIGIN_ADMIN,
        ?int $createdBy = null,
    ): void {
        $norm = self::normalize($source);

        if ($norm === '') {
            return;
        }

        $existing = self::query()
            ->where('taxonomy', $taxonomy)
            ->where('source_norm', $norm)
            ->first();

        if ($existing !== null
            && $existing->origin === self::ORIGIN_ADMIN
            && $origin !== self::ORIGIN_ADMIN) {
            return;
        }

        self::query()->updateOrCreate(
            ['taxonomy' => $taxonomy, 'source_norm' => $norm],
            [
                'source' => trim((string) $source),
                'target_id' => $targetId,
                'origin' => $origin,
                'created_by' => $createdBy,
            ],
        );
    }
}
