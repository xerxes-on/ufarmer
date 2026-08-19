<?php

declare(strict_types=1);

namespace Modules\Agronom\Filament\Resources;

use App\Filament\NavigationGroup;
use App\Traits\HasTranslatedFilamentLabels;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Agronom\Enums\ServiceRequestStatus;
use Modules\Agronom\Enums\ServiceRequestType;
use Modules\Agronom\Filament\Resources\ServiceRequestResource\Pages;
use Modules\Agronom\Models\ServiceRequest;

class ServiceRequestResource extends Resource
{
    use HasTranslatedFilamentLabels;

    protected static ?string $model = ServiceRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Service Requests';

    protected static ?string $navigationGroup = NavigationGroup::ServicesAndJobs->value;

    protected static ?int $navigationSort = 60;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('admin-panel.resources.service_request.sections.request_details'))
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->label(__('admin-panel.resources.service_request.fields.type'))
                            ->options(collect(ServiceRequestType::cases())->mapWithKeys(
                                fn (ServiceRequestType $type) => [$type->value => $type->label()]
                            ))
                            ->required(),
                        Forms\Components\Select::make('status')
                            ->label(__('admin-panel.resources.service_request.fields.status'))
                            ->options(collect(ServiceRequestStatus::cases())->mapWithKeys(
                                fn (ServiceRequestStatus $status) => [$status->value => $status->label()]
                            ))
                            ->required(),
                        Forms\Components\TextInput::make('price')
                            ->label(__('admin-panel.resources.service_request.fields.price'))
                            ->numeric()
                            ->prefix('UZS'),
                    ])
                    ->columns(3),

                Forms\Components\Section::make(__('admin-panel.resources.service_request.sections.participants'))
                    ->schema([
                        Forms\Components\Placeholder::make('farmer_info')
                            ->label(__('admin-panel.resources.service_request.fields.farmer'))
                            ->content(fn ($record) => $record?->farmer
                                ? "{$record->farmer->name} ({$record->farmer->phone})"
                                : '-'),
                        Forms\Components\Placeholder::make('agronom_info')
                            ->label(__('admin-panel.resources.service_request.fields.agronom'))
                            ->content(fn ($record) => $record?->agronom
                                ? "{$record->agronom->name} ({$record->agronom->phone})"
                                : '-'),
                        Forms\Components\Placeholder::make('service_info')
                            ->label(__('admin-panel.resources.service_request.fields.service'))
                            ->content(fn ($record) => $record?->service?->name ?? '-'),
                    ])
                    ->columns(3),

                Forms\Components\Section::make(__('admin-panel.resources.service_request.sections.location'))
                    ->schema([
                        Forms\Components\TextInput::make('latitude')
                            ->label(__('admin-panel.resources.service_request.fields.latitude'))
                            ->numeric()
                            ->step(0.00000001),
                        Forms\Components\TextInput::make('longitude')
                            ->label(__('admin-panel.resources.service_request.fields.longitude'))
                            ->numeric()
                            ->step(0.00000001),
                    ])
                    ->columns(2),

                Forms\Components\Section::make(__('admin-panel.resources.service_request.sections.scheduling'))
                    ->schema([
                        Forms\Components\DateTimePicker::make('scheduled_time')
                            ->label(__('admin-panel.resources.service_request.fields.scheduled_time')),
                    ]),

                Forms\Components\Section::make(__('admin-panel.resources.service_request.sections.additional_info'))
                    ->schema([
                        Forms\Components\TagsInput::make('crop_ids')
                            ->label(__('admin-panel.resources.service_request.fields.crop_ids'))
                            ->placeholder(__('admin-panel.resources.service_request.placeholders.add_crop_id'))
                            ->disabled(),
                    ]),

                Forms\Components\Section::make(__('admin-panel.resources.service_request.sections.monitoring_calendar_runs'))
                    ->schema([
                        Forms\Components\Placeholder::make('calendar_runs_info')
                            ->label(__('admin-panel.resources.service_request.fields.assigned_calendar_runs'))
                            ->content(function ($record) {
                                if (! $record || ! $record->isMonitoring()) {
                                    return __('admin-panel.resources.service_request.messages.not_monitoring');
                                }

                                $pivots = $record->requestCalendarRuns()->with('calendarRun')->get();

                                if ($pivots->isEmpty()) {
                                    return __('admin-panel.resources.service_request.messages.no_calendar_runs');
                                }

                                return $pivots->map(function ($pivot) {
                                    $run = $pivot->calendarRun;
                                    $name = __('admin-panel.resources.service_request.messages.calendar_run', [
                                        'id' => $run?->id ?? $pivot->calendar_run_id,
                                    ]);

                                    return $name.' — '.__('admin-panel.resources.service_request.calendar_statuses.'.$pivot->status->value);
                                })->implode("\n");
                            }),
                    ])
                    ->visible(fn ($record) => $record?->isMonitoring() ?? false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label(__('admin-panel.resources.service_request.fields.id'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('farmer.name')
                    ->label(__('admin-panel.resources.service_request.fields.farmer'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('agronom.name')
                    ->label(__('admin-panel.resources.service_request.fields.agronom'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('service.name')
                    ->label(__('admin-panel.resources.service_request.fields.service'))
                    ->limit(30)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('type')
                    ->label(__('admin-panel.resources.service_request.fields.type'))
                    ->badge()
                    ->formatStateUsing(fn (ServiceRequestType $state) => $state->label())
                    ->color(fn (ServiceRequestType $state) => $state->color()),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('admin-panel.resources.service_request.fields.status'))
                    ->badge()
                    ->formatStateUsing(fn (ServiceRequestStatus $state) => $state->label())
                    ->color(fn (ServiceRequestStatus $state) => $state->color()),
                Tables\Columns\TextColumn::make('price')
                    ->label(__('admin-panel.resources.service_request.fields.price'))
                    ->money('UZS')
                    ->sortable(),
                Tables\Columns\TextColumn::make('requestCalendarRuns')
                    ->label(__('admin-panel.resources.service_request.fields.calendars'))
                    ->getStateUsing(fn ($record) => $record->requestCalendarRuns()->count())
                    ->badge()
                    ->color('info')
                    ->visible(fn () => true)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('scheduled_time')
                    ->label(__('admin-panel.resources.service_request.fields.scheduled'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('admin-panel.resources.service_request.fields.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('admin-panel.resources.service_request.fields.status'))
                    ->options(collect(ServiceRequestStatus::cases())->mapWithKeys(
                        fn (ServiceRequestStatus $status) => [$status->value => $status->label()]
                    )),
                Tables\Filters\SelectFilter::make('type')
                    ->label(__('admin-panel.resources.service_request.fields.type'))
                    ->options(collect(ServiceRequestType::cases())->mapWithKeys(
                        fn (ServiceRequestType $type) => [$type->value => $type->label()]
                    )),
                Tables\Filters\Filter::make('has_price')
                    ->label(__('admin-panel.resources.service_request.filters.has_price'))
                    ->query(fn (Builder $query) => $query->whereNotNull('price')->where('price', '>', 0)),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    protected static function translationKey(): string
    {
        return 'service_request';
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServiceRequests::route('/'),
            'create' => Pages\CreateServiceRequest::route('/create'),
            'edit' => Pages\EditServiceRequest::route('/{record}/edit'),
        ];
    }
}
