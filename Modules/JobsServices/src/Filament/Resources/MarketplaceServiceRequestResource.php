<?php

declare(strict_types=1);

namespace Modules\JobsServices\Filament\Resources;

use App\Filament\NavigationGroup;
use Filament\Forms\Form;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Modules\JobsServices\Enums\WorkerActivationState;
use Modules\JobsServices\Filament\Resources\MarketplaceServiceRequestResource\Pages;
use Modules\JobsServices\Models\MarketplaceServiceRequest;
use Modules\JobsServices\Models\ServiceOffer;
use Modules\JobsServices\Models\UserProfile;
use Modules\JobsServices\Support\WorkerMetadata;

class MarketplaceServiceRequestResource extends Resource
{
    private const string WORKER_METADATA_SCHEMA_CACHE_KEY = self::class.'.worker-metadata-schema';

    protected static ?string $model = MarketplaceServiceRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationGroup = NavigationGroup::ServicesAndJobs->value;

    protected static ?int $navigationSort = 31;

    public static function getNavigationLabel(): string
    {
        return __('admin-panel.resources.marketplace_service_request.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('admin-panel.resources.marketplace_service_request.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin-panel.resources.marketplace_service_request.plural_model_label');
    }

    public static function getNavigationBadge(): ?string
    {
        if (! self::workerMetadataIsAvailable()) {
            return null;
        }

        return (string) MarketplaceServiceRequest::query()->needsManualInspection()->count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return __('admin-panel.resources.marketplace_service_request.badge_tooltip');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return self::workerMetadataIsAvailable() && parent::shouldRegisterNavigation();
    }

    public static function canViewAny(): bool
    {
        return self::workerMetadataIsAvailable() && parent::canViewAny();
    }

    public static function canView(Model $record): bool
    {
        return self::workerMetadataIsAvailable() && parent::canView($record);
    }

    public static function workerMetadataIsAvailable(): bool
    {
        $request = request();

        if ($request->attributes->has(self::WORKER_METADATA_SCHEMA_CACHE_KEY)) {
            return (bool) $request->attributes->get(self::WORKER_METADATA_SCHEMA_CACHE_KEY);
        }

        $available = Schema::hasTable('user_profiles') && Schema::hasColumn('user_profiles', 'meta');
        $request->attributes->set(self::WORKER_METADATA_SCHEMA_CACHE_KEY, $available);

        return $available;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label(__('admin-panel.resources.marketplace_service_request.fields.id'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('inspection')
                    ->label(__('admin-panel.resources.marketplace_service_request.fields.inspection'))
                    ->getStateUsing(fn (MarketplaceServiceRequest $record): string => $record->needsManualInspection()
                        ? 'required'
                        : 'not_required')
                    ->formatStateUsing(fn (string $state): string => __('admin-panel.resources.marketplace_service_request.states.'.$state))
                    ->badge()
                    ->color(fn (string $state): string => $state === 'required' ? 'danger' : 'success'),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('admin-panel.resources.marketplace_service_request.fields.status'))
                    ->formatStateUsing(fn (string $state): string => __('admin-panel.resources.marketplace_service_request.statuses.'.$state))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'accepted' => 'info',
                        'completed' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('offer_title')
                    ->label(__('admin-panel.resources.marketplace_service_request.fields.service'))
                    ->getStateUsing(fn (MarketplaceServiceRequest $record): string => self::offerTitle($record->offer))
                    ->wrap(),
                Tables\Columns\TextColumn::make('offer.category.localized_name')
                    ->label(__('admin-panel.resources.marketplace_service_request.fields.category'))
                    ->placeholder('-')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('requester.name')
                    ->label(__('admin-panel.resources.marketplace_service_request.fields.requester'))
                    ->searchable()
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('requester.phone')
                    ->label(__('admin-panel.resources.marketplace_service_request.fields.requester_phone'))
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('offer.user.name')
                    ->label(__('admin-panel.resources.marketplace_service_request.fields.worker'))
                    ->searchable()
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('offer.user.phone')
                    ->label(__('admin-panel.resources.marketplace_service_request.fields.worker_phone'))
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('worker_origin')
                    ->label(__('admin-panel.resources.marketplace_service_request.fields.worker_origin'))
                    ->getStateUsing(fn (MarketplaceServiceRequest $record): string => WorkerMetadata::isAiAdded(
                        $record->offer?->user?->workerProfile?->meta
                    ) ? 'ai_added' : 'existing')
                    ->formatStateUsing(fn (string $state): string => __('admin-panel.resources.worker.states.'.$state))
                    ->badge()
                    ->color(fn (string $state): string => $state === 'ai_added' ? 'warning' : 'gray'),
                Tables\Columns\TextColumn::make('worker_activation')
                    ->label(__('admin-panel.resources.marketplace_service_request.fields.worker_activation'))
                    ->getStateUsing(fn (MarketplaceServiceRequest $record): string => self::workerActivationState(
                        $record->offer?->user?->workerProfile,
                        $record->offer?->user?->auth_id,
                    ))
                    ->formatStateUsing(fn (string $state): string => __('admin-panel.resources.worker.states.'.$state))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        WorkerActivationState::Activated->value => 'success',
                        WorkerActivationState::NotActivated->value => 'warning',
                        WorkerActivationState::AuthMissing->value => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('proposed_price')
                    ->label(__('admin-panel.resources.marketplace_service_request.fields.proposed_price'))
                    ->money(fn (MarketplaceServiceRequest $record): string => $record->currency ?? 'UZS')
                    ->placeholder('-')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('admin-panel.resources.marketplace_service_request.fields.created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('admin-panel.resources.marketplace_service_request.fields.status'))
                    ->options([
                        'pending' => __('admin-panel.resources.marketplace_service_request.statuses.pending'),
                        'accepted' => __('admin-panel.resources.marketplace_service_request.statuses.accepted'),
                        'completed' => __('admin-panel.resources.marketplace_service_request.statuses.completed'),
                        'rejected' => __('admin-panel.resources.marketplace_service_request.statuses.rejected'),
                    ]),
                Tables\Filters\SelectFilter::make('inspection')
                    ->label(__('admin-panel.resources.marketplace_service_request.fields.inspection'))
                    ->options([
                        'required' => __('admin-panel.resources.marketplace_service_request.states.required'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => ($data['value'] ?? null) === 'required'
                        ? $query->needsManualInspection()
                        : $query),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Section::make(__('admin-panel.resources.marketplace_service_request.sections.request'))
                ->schema([
                    TextEntry::make('id')
                        ->label(__('admin-panel.resources.marketplace_service_request.fields.id')),
                    TextEntry::make('status')
                        ->label(__('admin-panel.resources.marketplace_service_request.fields.status'))
                        ->formatStateUsing(fn (string $state): string => __('admin-panel.resources.marketplace_service_request.statuses.'.$state))
                        ->badge(),
                    TextEntry::make('inspection')
                        ->label(__('admin-panel.resources.marketplace_service_request.fields.inspection'))
                        ->state(fn (MarketplaceServiceRequest $record): string => $record->needsManualInspection()
                            ? __('admin-panel.resources.marketplace_service_request.states.required')
                            : __('admin-panel.resources.marketplace_service_request.states.not_required'))
                        ->badge()
                        ->color(fn (MarketplaceServiceRequest $record): string => $record->needsManualInspection() ? 'danger' : 'success'),
                    TextEntry::make('created_at')
                        ->label(__('admin-panel.resources.marketplace_service_request.fields.created_at'))
                        ->dateTime(),
                    TextEntry::make('responded_at')
                        ->label(__('admin-panel.resources.marketplace_service_request.fields.responded_at'))
                        ->dateTime()
                        ->placeholder('-'),
                    TextEntry::make('message')
                        ->label(__('admin-panel.resources.marketplace_service_request.fields.message'))
                        ->placeholder('-')
                        ->columnSpanFull(),
                    TextEntry::make('proposed_price')
                        ->label(__('admin-panel.resources.marketplace_service_request.fields.proposed_price'))
                        ->money(fn (MarketplaceServiceRequest $record): string => $record->currency ?? 'UZS')
                        ->placeholder('-'),
                ])
                ->columns(3),
            Section::make(__('admin-panel.resources.marketplace_service_request.sections.parties'))
                ->schema([
                    TextEntry::make('requester.name')
                        ->label(__('admin-panel.resources.marketplace_service_request.fields.requester'))
                        ->placeholder('-'),
                    TextEntry::make('requester.phone')
                        ->label(__('admin-panel.resources.marketplace_service_request.fields.requester_phone'))
                        ->placeholder('-'),
                    TextEntry::make('offer.user.name')
                        ->label(__('admin-panel.resources.marketplace_service_request.fields.worker'))
                        ->placeholder('-'),
                    TextEntry::make('offer.user.phone')
                        ->label(__('admin-panel.resources.marketplace_service_request.fields.worker_phone'))
                        ->placeholder('-'),
                    TextEntry::make('worker_origin')
                        ->label(__('admin-panel.resources.marketplace_service_request.fields.worker_origin'))
                        ->state(fn (MarketplaceServiceRequest $record): string => WorkerMetadata::isAiAdded(
                            $record->offer?->user?->workerProfile?->meta
                        )
                            ? __('admin-panel.resources.worker.states.ai_added')
                            : __('admin-panel.resources.worker.states.existing'))
                        ->badge(),
                    TextEntry::make('worker_activation')
                        ->label(__('admin-panel.resources.marketplace_service_request.fields.worker_activation'))
                        ->state(fn (MarketplaceServiceRequest $record): string => __('admin-panel.resources.worker.states.'.self::workerActivationState(
                            $record->offer?->user?->workerProfile,
                            $record->offer?->user?->auth_id,
                        )))
                        ->badge(),
                ])
                ->columns(3),
            Section::make(__('admin-panel.resources.marketplace_service_request.sections.service'))
                ->schema([
                    TextEntry::make('offer_title')
                        ->label(__('admin-panel.resources.marketplace_service_request.fields.service'))
                        ->state(fn (MarketplaceServiceRequest $record): string => self::offerTitle($record->offer)),
                    TextEntry::make('offer.category.localized_name')
                        ->label(__('admin-panel.resources.marketplace_service_request.fields.category'))
                        ->placeholder('-'),
                    TextEntry::make('offer.address')
                        ->label(__('admin-panel.resources.marketplace_service_request.fields.address'))
                        ->placeholder('-'),
                ])
                ->columns(3),
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with([
            'requester',
            'offer.category',
            'offer.user.workerProfile',
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMarketplaceServiceRequests::route('/'),
            'view' => Pages\ViewMarketplaceServiceRequest::route('/{record}'),
        ];
    }

    private static function offerTitle(?ServiceOffer $offer): string
    {
        $translations = $offer?->getTitleTranslations() ?? [];

        return (string) ($translations[app()->getLocale()] ?? $translations['uz'] ?? $translations['ru'] ?? $translations['en'] ?? '-');
    }

    private static function workerActivationState(?UserProfile $profile, mixed $authId): string
    {
        if ($authId === null) {
            return WorkerActivationState::AuthMissing->value;
        }

        if ($profile !== null && WorkerMetadata::hasAppActivity($profile->meta)) {
            return WorkerActivationState::Activated->value;
        }

        return $profile !== null && WorkerMetadata::isAiAdded($profile->meta)
            ? WorkerActivationState::NotActivated->value
            : WorkerActivationState::NotTracked->value;
    }
}
