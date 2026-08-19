<?php

declare(strict_types=1);

namespace Modules\JobsServices\Services\AiImport\Worker;

use App\Models\User;
use App\Services\Auth\PanelAuthUserProvisioner;
use App\Support\AiImport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\Core\Models\City;
use Modules\Core\Models\Region;
use Modules\Core\Models\UserDetail;
use Modules\JobsServices\Enums\AiImportRowStatus;
use Modules\JobsServices\Enums\AiImportStatus;
use Modules\JobsServices\Models\AiImportBatch;
use Modules\JobsServices\Models\AiImportRow;
use Modules\JobsServices\Models\ServiceOffer;
use Modules\JobsServices\Models\UserProfile;
use Modules\JobsServices\Support\OfferLocales;
use Modules\JobsServices\Support\WorkerMetadata;
use Throwable;

/**
 * Turns reviewed rows into real workers and service offers (UFARM-2644).
 *
 * Each row publishes in **its own transaction**, so one bad row cannot roll
 * back a whole import — the admin gets the rest plus a list of what failed.
 *
 * Ordering matters: the auth account comes first, because identity is owned by
 * ufarm-auth and a local user without an `auth_id` cannot log in. A failure
 * there is deliberately *not* fatal — `resolveAuthId()` returns null and logs
 * on any RPC hiccup, and a blank `auth_id` is a supported "links up on first
 * SSO login" state. Refusing to import a worker because auth was briefly
 * unreachable would be the worse outcome.
 *
 * Idempotency comes from natural keys rather than bookkeeping: `users.phone`
 * is unique, `user_profiles.user_id` is unique, and offers are matched on
 * (user, category, title). Re-publishing updates rather than duplicating, and
 * only `draft` rows are considered at all — which is what lets a partly
 * published batch be corrected and published again.
 */
final class PublishWorkerImportAction
{
    public function __construct(
        private readonly PanelAuthUserProvisioner $provisioner = new PanelAuthUserProvisioner,
    ) {}

    /**
     * @return array{published: int, skipped: int, workers_created: int, offers_created: int, errors: array<int, string>}
     */
    public function execute(AiImportBatch $batch): array
    {
        if (! Schema::hasColumn('user_profiles', 'meta')) {
            throw new \RuntimeException('Worker metadata schema is not available');
        }

        $published = 0;
        $skipped = 0;
        $workersCreated = 0;
        $offersCreated = 0;
        $errors = [];

        $rows = $batch->rows()
            ->where('status', AiImportRowStatus::Draft)
            ->orderBy('row_index')
            ->get();

        foreach ($rows as $row) {
            if (! $row->isValid()) {
                $skipped++;

                continue;
            }

            try {
                $outcome = DB::transaction(fn (): array => $this->publishRow($row));

                $published++;
                $workersCreated += $outcome['worker_created'] ? 1 : 0;
                $offersCreated += $outcome['offer_created'] ? 1 : 0;
            } catch (Throwable $e) {
                $skipped++;
                $errors[] = sprintf('"%s": %s', (string) $row->label, $e->getMessage());

                Log::warning('AI import row failed to publish', [
                    'batch_id' => $batch->getKey(),
                    'row_id' => $row->getKey(),
                    'message' => $e->getMessage(),
                ]);
            }
        }

        // Publishing is resumable. A row that could not publish — because it
        // still carries a blocking error, or because it threw — stays a draft
        // an admin can fix, so declaring the whole batch Published would hide
        // the Publish button and strand it.
        //
        // Drafts, not publishable drafts: a row blocked on an unmatched
        // category is exactly the one that needs fixing, and counting only
        // rows that could publish *right now* would call the batch finished
        // precisely when it is most unfinished. An admin closes a batch out by
        // skipping what they do not want, which is what makes this terminate.
        $remaining = $batch->rows()->where('status', AiImportRowStatus::Draft)->count();

        $batch->forceFill([
            'status' => $remaining === 0 ? AiImportStatus::Published : AiImportStatus::Extracted,
            'rows_published' => $batch->rows()->where('status', AiImportRowStatus::Published)->count(),
            'stats' => array_merge((array) $batch->stats, [
                'workers_created' => $batch->stat('workers_created') + $workersCreated,
                'offers_created' => $batch->stat('offers_created') + $offersCreated,
            ]),
            'published_at' => now(),
        ])->save();

        return [
            'published' => $published,
            'skipped' => $skipped,
            'workers_created' => $workersCreated,
            'offers_created' => $offersCreated,
            'errors' => $errors,
        ];
    }

    /**
     * @return array{worker_created: bool, offer_created: bool}
     */
    private function publishRow(AiImportRow $row): array
    {
        $data = (array) $row->data;
        $phone = (string) ($data['phone'] ?? '');

        if ($phone === '') {
            throw new \RuntimeException('row has no usable phone number');
        }

        [$user] = $this->resolveUser($phone, $data);

        $this->linkAuthAccount($user, $phone, $row);
        $this->syncUserDetail($user, $data);
        [$profile, $profileCreated] = $this->syncProfile($user, $data);

        if ($profileCreated) {
            $profile->forceFill([
                'meta' => WorkerMetadata::withAiImportOrigin(
                    $profile->meta,
                    (int) $row->ai_import_batch_id,
                    (int) $row->getKey(),
                    now(),
                ),
            ])->save();
        }

        [$offer, $offerCreated] = $this->syncServiceOffer($user, $data);

        $row->forceFill([
            'status' => AiImportRowStatus::Published,
            'created_type' => $profile->getMorphClass(),
            'created_id' => $profile->getKey(),
            'data' => [
                ...$data,
                'user_id' => $user->getKey(),
                'service_offer_id' => $offer->getKey(),
                'auth_id' => $user->auth_id,
            ],
        ])->save();

        return ['worker_created' => $profileCreated, 'offer_created' => $offerCreated];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{0: User, 1: bool}
     */
    private function resolveUser(string $phone, array $data): array
    {
        // withTrashed: `users.phone` is unique and the table soft-deletes, so
        // an insert over a deleted account would hit the constraint instead.
        $user = User::withTrashed()->where('phone', $phone)->first();

        if ($user !== null) {
            if ($user->trashed()) {
                $user->restore();
            }

            // Only fill blanks — never overwrite what a human has curated.
            $name = $this->displayName($data);

            if (blank($user->name) && $name !== null) {
                $user->forceFill(['name' => $name])->save();
            }

            return [$user, false];
        }

        return [User::create([
            'name' => $this->displayName($data),
            'phone' => $phone,
            'language' => $data['language'] ?? OfferLocales::default(),
        ]), true];
    }

    /**
     * Create or find the ufarm-auth account and record its id locally.
     *
     * No password is ever sent: imported workers sign in by OTP, and
     * `findOrCreateUser` gives brand-new accounts an unusable random one.
     */
    private function linkAuthAccount(User $user, string $phone, AiImportRow $row): void
    {
        if (filled($user->auth_id)) {
            return;
        }

        $authId = $this->provisioner->resolveAuthId(
            phone: $phone,
            applicationAlias: AiImport::workerApplicationAlias(),
            password: null,
            entityId: (int) $user->getKey(),
        );

        if ($authId === null) {
            // Not fatal: the account links up on first SSO login.
            return;
        }

        // `users.auth_id` is unique. If another local user already holds this
        // id the data is ambiguous, and guessing would corrupt an identity.
        $conflict = User::withTrashed()
            ->where('auth_id', $authId)
            ->whereKeyNot($user->getKey())
            ->exists();

        if ($conflict) {
            Log::warning('AI import: auth_id already belongs to another user', [
                'row_id' => $row->getKey(),
                'auth_id' => $authId,
                'user_id' => $user->getKey(),
            ]);

            return;
        }

        $user->forceFill(['auth_id' => $authId])->save();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function syncUserDetail(User $user, array $data): void
    {
        $detail = UserDetail::withTrashed()->firstOrNew(['user_id' => $user->getKey()]);

        if ($detail->exists && $detail->trashed()) {
            $detail->restore();
        }

        // Fill only what is missing: an admin's earlier correction outranks
        // anything a spreadsheet says.
        $fill = [
            'region_id' => $data['region_id'] ?? null,
            'city_id' => $data['city_id'] ?? null,
            'address' => $data['address'] ?? null,
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'language' => $data['language'] ?? OfferLocales::default(),
        ];

        foreach ($fill as $column => $value) {
            if ($value !== null && blank($detail->{$column})) {
                $detail->{$column} = $value;
            }
        }

        $detail->save();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function syncProfile(User $user, array $data): array
    {
        $profile = UserProfile::firstOrNew(['user_id' => $user->getKey()]);
        $created = ! $profile->exists;

        $bio = $this->composeBio($data);

        if ($bio !== null && blank($profile->bio)) {
            $profile->bio = $bio;
        }

        if (($data['experience_years'] ?? null) !== null && blank($profile->experience_years)) {
            $profile->experience_years = $data['experience_years'];
        }

        $specializations = $data['specializations'] ?? [];

        if (is_array($specializations) && $specializations !== [] && blank($profile->specializations)) {
            $profile->specializations = array_values($specializations);
        }

        if (! $profile->exists) {
            $profile->is_available = true;
            $profile->experience_years ??= 0;
        }

        // Deliberately not written: working_hours_from/to, education, price.
        // They are absent from the deployed table (ufarm-api guards every
        // write to them with Schema::hasColumn), so an INSERT naming them
        // would fail in production. Working hours ride along in the bio.
        $profile->save();

        return [$profile, $created];
    }

    /**
     * Fold the sheet's extra prose into the bio, since the profile table has
     * nowhere else for a company name or working hours to live.
     *
     * @param  array<string, mixed>  $data
     */
    private function composeBio(array $data): ?string
    {
        $parts = array_filter([
            is_string($data['bio'] ?? null) ? $data['bio'] : null,
            is_string($data['company'] ?? null) ? $data['company'] : null,
            is_string($data['working_hours'] ?? null)
                ? __('admin-panel.resources.ai_import.fields.working_hours').': '.$data['working_hours']
                : null,
        ]);

        if ($parts === []) {
            return null;
        }

        return mb_substr(implode("\n", $parts), 0, 1000);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{0: ServiceOffer, 1: bool}
     */
    private function syncServiceOffer(User $user, array $data): array
    {
        $title = (string) ($data['title'] ?? '');
        $categoryId = $data['category_id'] ?? null;

        if ($title === '' || $categoryId === null) {
            throw new \RuntimeException('row has no service title or category');
        }

        // Defense-in-depth backstop (UFARM-2767) — WorkerRowValidator already
        // blocks this at review time; this only fires if a row's stored
        // errors are stale.
        if ((float) ($data['price'] ?? 0) > WorkerRowValidator::MAX_PRICE) {
            throw new \RuntimeException('price exceeds maximum ('.WorkerRowValidator::MAX_PRICE.')');
        }

        $titleTranslations = $this->localizedValues(
            $data['title_translations'] ?? null,
            $title,
            255,
        );
        $titleCandidates = array_values(array_unique([$title, ...array_values($titleTranslations)]));

        // Match on the natural key so re-publishing updates rather than
        // duplicating. Compared case-insensitively: the same service retyped
        // with different capitalisation is the same service.
        //
        // getTitleTranslations() rather than ->title: ServiceOffer's accessor
        // collapses the JSON to the current locale's string, so reading
        // ->title['uz'] would index into a string, not the translations.
        $existing = ServiceOffer::query()
            ->where('user_id', $user->getKey())
            ->where('category_id', $categoryId)
            ->get()
            ->first(function (ServiceOffer $offer) use ($titleCandidates): bool {
                foreach ($offer->getTitleTranslations() as $translation) {
                    if (! is_string($translation)) {
                        continue;
                    }

                    foreach ($titleCandidates as $candidate) {
                        if (Str::lower(trim($translation)) === Str::lower(trim($candidate))) {
                            return true;
                        }
                    }
                }

                return false;
            });

        $offer = $existing ?? new ServiceOffer;
        $created = $existing === null;

        $description = $this->composeDescription($data);
        $descriptionTranslations = $this->localizedValues(
            $data['description_translations'] ?? null,
            $description,
            2000,
        );

        $offer->fill([
            'user_id' => $user->getKey(),
            'category_id' => $categoryId,
            'title' => $titleTranslations,
            // An empty array rather than null: ServiceOffer::getDescriptionAttribute()
            // calls json_decode() on the raw value unguarded, so a null column
            // throws a TypeError on read. No offer currently stores null, and
            // this importer must not be the first to create one.
            'description' => $descriptionTranslations,
            'price' => $data['price'] ?? 0,
            'price_unit' => $data['price_unit'] ?? 'per_project',
            'currency' => $data['currency'] ?? 'UZS',
            // NOT NULL, and the mapper guarantees a value via its fallbacks.
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
            'address' => $data['address'] ?? null,
            // Free-text on this table, not foreign keys.
            'city' => $this->placeName(City::class, $data['city_id'] ?? null) ?? ($data['city_name'] ?? null),
            'region' => $this->placeName(Region::class, $data['region_id'] ?? null) ?? ($data['region_name'] ?? null),
            'status' => 'open',
        ]);

        $offer->save();

        return [$offer, $created];
    }

    /**
     * @return array<string, string>
     */
    private function localizedValues(mixed $values, ?string $fallback, int $maxLength): array
    {
        $values = is_array($values) ? $values : [];
        $localized = [];

        foreach (OfferLocales::all() as $locale) {
            $value = $values[$locale] ?? null;
            $value = is_string($value) && trim($value) !== '' ? trim($value) : $fallback;

            if (is_string($value) && trim($value) !== '') {
                $localized[$locale] = mb_substr(trim($value), 0, $maxLength);
            }
        }

        return $localized;
    }

    /**
     * Carry everything the offer has no column for into the description, so
     * nothing the sheet stated is silently lost.
     *
     * @param  array<string, mixed>  $data
     */
    private function composeDescription(array $data): ?string
    {
        $parts = [is_string($data['description'] ?? null) ? $data['description'] : null];

        // A negotiable price stores as 0; without this the offer would read
        // "free" instead of "по договору".
        if (($data['price_negotiable'] ?? false) && is_string($data['price_raw'] ?? null)) {
            $parts[] = __('admin-panel.resources.ai_import.fields.price').': '.$data['price_raw'];
        }

        if (is_string($data['area'] ?? null)) {
            $parts[] = __('admin-panel.resources.ai_import.fields.area').': '.$data['area'];
        }

        if (is_string($data['working_hours'] ?? null)) {
            $parts[] = __('admin-panel.resources.ai_import.fields.working_hours').': '.$data['working_hours'];
        }

        $parts = array_filter($parts);

        return $parts === [] ? null : mb_substr(implode("\n", $parts), 0, 2000);
    }

    /**
     * @param  class-string<Region|City>  $model
     */
    private function placeName(string $model, ?int $id): ?string
    {
        if ($id === null) {
            return null;
        }

        $record = $model::find($id);

        return $record?->localized_name;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function displayName(array $data): ?string
    {
        $name = $data['person_name'] ?? null;

        if (is_string($name) && trim($name) !== '') {
            return $name;
        }

        $company = $data['company'] ?? null;

        return is_string($company) && trim($company) !== '' ? $company : null;
    }
}
