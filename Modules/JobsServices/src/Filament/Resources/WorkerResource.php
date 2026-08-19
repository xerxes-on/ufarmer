<?php

declare(strict_types=1);

namespace Modules\JobsServices\Filament\Resources;

use App\Filament\NavigationGroup;
use App\Models\User;
use App\Traits\HasTranslatedFilamentLabels;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\JobsServices\Enums\WorkerActivationState;
use Modules\JobsServices\Filament\Resources\WorkerResource\Pages;
use Modules\JobsServices\Filament\Resources\WorkerResource\RelationManagers;
use Modules\JobsServices\Models\UserProfile;
use Modules\JobsServices\Support\WorkerMetadata;

/**
 * Workers — users who offer services on the marketplace.
 *
 * The model is `UserProfile` rather than `User` because "worker" is a role a
 * user takes on, not a separate account: the profile row is what makes a user
 * a worker. Scoping the resource to `user_profiles` keeps ordinary farmers out
 * of this list without a magic role filter.
 *
 * Services are managed through the Services relation manager rather than a
 * repeater, so each service offer keeps its own create/edit/delete audit trail
 * and validation instead of being rewritten wholesale on every profile save.
 */
class WorkerResource extends Resource
{
    use HasTranslatedFilamentLabels;

    protected static ?string $model = UserProfile::class;

    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static ?string $navigationLabel = 'Workers';

    protected static ?string $modelLabel = 'Worker';

    protected static ?string $pluralModelLabel = 'Workers';

    protected static ?string $navigationGroup = NavigationGroup::ServicesAndJobs->value;

    protected static ?int $navigationSort = 30;

    protected static function translationKey(): string
    {
        return 'worker';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('admin-panel.resources.worker.sections.account'))
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label(__('admin-panel.resources.worker.fields.user'))
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->disabled(fn (string $operation): bool => $operation === 'edit')
                            ->dehydrated()
                            ->helperText(__('admin-panel.resources.worker.help.user'))
                            ->getSearchResultsUsing(fn (string $search): array => User::query()
                                ->select(['id', 'name', 'phone'])
                                ->where(fn (Builder $query) => $query
                                    ->where('phone', 'like', "%{$search}%")
                                    ->orWhere('name', 'like', "%{$search}%"))
                                ->limit(20)
                                ->get()
                                ->mapWithKeys(fn (User $user): array => [$user->id => self::userLabel($user)])
                                ->all())
                            ->getOptionLabelUsing(fn ($value): ?string => ($user = User::find($value))
                                ? self::userLabel($user)
                                : null)
                            ->createOptionForm(self::userFields())
                            ->createOptionUsing(fn (array $data): int => self::createWorkerUser($data))
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make(__('admin-panel.resources.worker.sections.profile'))
                    ->schema([
                        Forms\Components\Textarea::make('bio')
                            ->label(__('admin-panel.resources.worker.fields.bio'))
                            ->rows(3)
                            ->maxLength(1000)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('experience_years')
                            ->label(__('admin-panel.resources.worker.fields.experience_years'))
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(99)
                            ->step(0.1)
                            ->default(0),
                        Forms\Components\Toggle::make('is_available')
                            ->label(__('admin-panel.resources.worker.fields.is_available'))
                            ->default(true),
                        Forms\Components\TagsInput::make('specializations')
                            ->label(__('admin-panel.resources.worker.fields.specializations'))
                            ->helperText(__('admin-panel.resources.worker.help.specializations'))
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['user.detail.region', 'user.detail.city']))
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label(__('admin-panel.resources.worker.fields.name'))
                    ->searchable()
                    ->sortable()
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('user.phone')
                    ->label(__('admin-panel.resources.worker.fields.phone'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.detail.region')
                    ->label(__('admin-panel.resources.worker.fields.region'))
                    ->getStateUsing(fn (UserProfile $record) => $record->user?->detail?->region?->localized_name)
                    ->placeholder('-')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('user.detail.city')
                    ->label(__('admin-panel.resources.worker.fields.city'))
                    ->getStateUsing(fn (UserProfile $record) => $record->user?->detail?->city?->localized_name)
                    ->placeholder('-')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('services_count')
                    ->label(__('admin-panel.resources.worker.fields.services'))
                    ->counts('services')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('origin')
                    ->label(__('admin-panel.resources.worker.fields.origin'))
                    ->getStateUsing(fn (UserProfile $record): string => WorkerMetadata::isAiAdded($record->meta)
                        ? 'ai_added'
                        : 'existing')
                    ->formatStateUsing(fn (string $state): string => __('admin-panel.resources.worker.states.'.$state))
                    ->badge()
                    ->color(fn (string $state): string => $state === 'ai_added' ? 'warning' : 'gray'),
                Tables\Columns\TextColumn::make('activation')
                    ->label(__('admin-panel.resources.worker.fields.activation'))
                    ->getStateUsing(fn (UserProfile $record): string => self::activationState($record))
                    ->formatStateUsing(fn (string $state): string => __('admin-panel.resources.worker.states.'.$state))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        WorkerActivationState::Activated->value => 'success',
                        WorkerActivationState::NotActivated->value => 'warning',
                        WorkerActivationState::AuthMissing->value => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('auth_login')
                    ->label(__('admin-panel.resources.worker.fields.auth_login'))
                    ->getStateUsing(fn (UserProfile $record): string => WorkerMetadata::firstAuthLoginAt($record->meta) !== null
                        ? 'logged_in'
                        : (WorkerMetadata::isAiAdded($record->meta) ? 'never_logged_in' : 'not_tracked'))
                    ->formatStateUsing(fn (string $state): string => __('admin-panel.resources.worker.states.'.$state))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'logged_in' => 'success',
                        'never_logged_in' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('first_auth_login_at')
                    ->label(__('admin-panel.resources.worker.fields.first_auth_login_at'))
                    ->getStateUsing(fn (UserProfile $record) => WorkerMetadata::firstAuthLoginAt($record->meta))
                    ->dateTime()
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('first_service_app_seen_at')
                    ->label(__('admin-panel.resources.worker.fields.first_service_app_seen_at'))
                    ->getStateUsing(fn (UserProfile $record) => WorkerMetadata::firstServiceAppSeenAt($record->meta))
                    ->dateTime()
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('experience_years')
                    ->label(__('admin-panel.resources.worker.fields.experience_years'))
                    ->numeric(decimalPlaces: 1)
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\IconColumn::make('is_available')
                    ->label(__('admin-panel.resources.worker.fields.is_available'))
                    ->boolean(),
                Tables\Columns\TextColumn::make('rating')
                    ->label(__('admin-panel.resources.worker.fields.rating'))
                    ->numeric(decimalPlaces: 2)
                    ->placeholder('-')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_available')
                    ->label(__('admin-panel.resources.worker.fields.is_available'))
                    ->native(false),
                Tables\Filters\SelectFilter::make('region')
                    ->label(__('admin-panel.resources.worker.fields.region'))
                    ->options(fn (): array => \Modules\Core\Models\Region::query()
                        ->get()
                        ->mapWithKeys(fn ($region): array => [$region->id => $region->localized_name])
                        ->all())
                    ->query(fn (Builder $query, array $data): Builder => filled($data['value'] ?? null)
                        ? $query->whereHas('user.detail', fn (Builder $q) => $q->where('region_id', $data['value']))
                        : $query),
                Tables\Filters\SelectFilter::make('origin')
                    ->label(__('admin-panel.resources.worker.fields.origin'))
                    ->options([
                        'ai_added' => __('admin-panel.resources.worker.states.ai_added'),
                        'existing' => __('admin-panel.resources.worker.states.existing'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => match ($data['value'] ?? null) {
                        'ai_added' => $query->where('meta->origin->type', WorkerMetadata::AI_IMPORT),
                        'existing' => $query->where(fn (Builder $inner) => $inner
                            ->whereNull('meta->origin->type')
                            ->orWhere('meta->origin->type', '!=', WorkerMetadata::AI_IMPORT)),
                        default => $query,
                    }),
                Tables\Filters\SelectFilter::make('activation')
                    ->label(__('admin-panel.resources.worker.fields.activation'))
                    ->options([
                        WorkerActivationState::Activated->value => __('admin-panel.resources.worker.states.'.WorkerActivationState::Activated->value),
                        WorkerActivationState::NotActivated->value => __('admin-panel.resources.worker.states.'.WorkerActivationState::NotActivated->value),
                        WorkerActivationState::AuthMissing->value => __('admin-panel.resources.worker.states.'.WorkerActivationState::AuthMissing->value),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => match ($data['value'] ?? null) {
                        WorkerActivationState::Activated->value => WorkerMetadata::whereAppActivityIsValid($query)
                            ->whereHas('user', fn (Builder $user) => $user->whereNotNull('auth_id')),
                        WorkerActivationState::NotActivated->value => WorkerMetadata::whereAppActivityIsMissing(
                            $query->where('meta->origin->type', WorkerMetadata::AI_IMPORT),
                        )
                            ->whereHas('user', fn (Builder $user) => $user->whereNotNull('auth_id')),
                        WorkerActivationState::AuthMissing->value => $query->whereHas('user', fn (Builder $user) => $user->whereNull('auth_id')),
                        default => $query,
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ServicesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWorkers::route('/'),
            'create' => Pages\CreateWorker::route('/create'),
            'edit' => Pages\EditWorker::route('/{record}/edit'),
        ];
    }

    /**
     * Fields for creating a brand-new user inline from the worker picker.
     * Region/city are stored on `user_details`, not `users`, so they are
     * applied in createWorkerUser() rather than dehydrated directly.
     *
     * @return array<int, Forms\Components\Component>
     */
    public static function userFields(): array
    {
        return [
            Forms\Components\TextInput::make('name')
                ->label(__('admin-panel.resources.worker.fields.name'))
                ->required()
                ->maxLength(255),
            Forms\Components\TextInput::make('phone')
                ->label(__('admin-panel.resources.worker.fields.phone'))
                ->tel()
                ->required()
                ->unique('users', 'phone')
                ->maxLength(20)
                ->helperText(__('admin-panel.resources.worker.help.phone')),
            Forms\Components\Select::make('language')
                ->label(__('admin-panel.resources.worker.fields.language'))
                ->options(self::languageOptions())
                ->default('uz')
                ->required()
                ->native(false),
            Forms\Components\Select::make('region_id')
                ->label(__('admin-panel.resources.worker.fields.region'))
                ->options(fn (): array => \Modules\Core\Models\Region::query()
                    ->get()
                    ->mapWithKeys(fn ($region): array => [$region->id => $region->localized_name])
                    ->all())
                ->searchable()
                ->required()
                ->live()
                // Clear a stale city whenever the region changes, otherwise the
                // previously-picked city stays selected but is no longer in the
                // (region-scoped) option list and silently saves a mismatch.
                ->afterStateUpdated(fn (Forms\Set $set) => $set('city_id', null))
                ->native(false),
            Forms\Components\Select::make('city_id')
                ->label(__('admin-panel.resources.worker.fields.city'))
                ->options(fn (Forms\Get $get): array => filled($get('region_id'))
                    ? \Modules\Core\Models\City::query()
                        ->where('region_id', $get('region_id'))
                        ->get()
                        ->mapWithKeys(fn ($city): array => [$city->id => $city->localized_name])
                        ->all()
                    : [])
                ->searchable()
                ->required()
                ->disabled(fn (Forms\Get $get): bool => blank($get('region_id')))
                ->helperText(__('admin-panel.resources.worker.help.city'))
                ->native(false),
        ];
    }

    /**
     * Creates the `users` row plus its `user_details` (region/city live there),
     * and returns the new id for the picker.
     *
     * @param  array<string, mixed>  $data
     */
    public static function createWorkerUser(array $data): int
    {
        $user = User::create([
            'name' => $data['name'],
            'phone' => $data['phone'],
            'language' => $data['language'] ?? 'uz',
        ]);

        $user->detail()->create([
            'region_id' => $data['region_id'] ?? null,
            'city_id' => $data['city_id'] ?? null,
            'language' => $data['language'] ?? 'uz',
        ]);

        return $user->getKey();
    }

    /**
     * @return array<string, string>
     */
    public static function languageOptions(): array
    {
        return [
            'uz' => __('admin-panel.locales.uz'),
            'ru' => __('admin-panel.locales.ru'),
            'en' => __('admin-panel.locales.en'),
        ];
    }

    private static function userLabel(User $user): string
    {
        $name = trim((string) $user->name);

        return $name !== '' ? "{$name} ({$user->phone})" : (string) $user->phone;
    }

    private static function activationState(UserProfile $profile): string
    {
        if ($profile->user?->auth_id === null) {
            return WorkerActivationState::AuthMissing->value;
        }

        if (WorkerMetadata::hasAppActivity($profile->meta)) {
            return WorkerActivationState::Activated->value;
        }

        return WorkerMetadata::isAiAdded($profile->meta)
            ? WorkerActivationState::NotActivated->value
            : WorkerActivationState::NotTracked->value;
    }
}
