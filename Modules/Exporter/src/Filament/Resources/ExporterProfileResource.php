<?php

declare(strict_types=1);

namespace Modules\Exporter\Filament\Resources;

use App\Events\RawNotificationEvent;
use App\Filament\NavigationGroup;
use App\Traits\HasTranslatedFilamentLabels;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Exporter\Enums\ExporterAccessRequestStatus;
use Modules\Exporter\Filament\Resources\ExporterProfileResource\Pages;
use Modules\Exporter\Models\ExporterProfile;
use Modules\Exporter\Models\ExporterRole;

class ExporterProfileResource extends Resource
{
    use HasTranslatedFilamentLabels;

    protected static ?string $model = ExporterProfile::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = NavigationGroup::MarketplaceAndPrices->value;

    protected static ?int $navigationSort = 70;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make(__('exporter::filament.resources.profile.sections.company'))
                ->schema([
                    Forms\Components\TextInput::make('full_name')
                        ->label(__('exporter::filament.resources.profile.fields.full_name'))
                        ->maxLength(255),
                    Forms\Components\TextInput::make('position')
                        ->label(__('exporter::filament.resources.profile.fields.position'))
                        ->maxLength(255),
                    Forms\Components\TextInput::make('company_name')
                        ->label(__('exporter::filament.resources.profile.fields.company_name'))
                        ->maxLength(255),
                    Forms\Components\TextInput::make('license_number')
                        ->label(__('exporter::filament.resources.profile.fields.license_number'))
                        ->maxLength(255),
                    Forms\Components\TextInput::make('inn')
                        ->label(__('exporter::filament.resources.profile.fields.inn'))
                        ->maxLength(50),
                    Forms\Components\Textarea::make('bio')
                        ->label(__('exporter::filament.resources.profile.fields.bio'))
                        ->rows(3),
                ])
                ->columns(2),

            Forms\Components\Section::make(__('exporter::filament.resources.profile.sections.access_request'))
                ->schema([
                    Forms\Components\Select::make('access_request_status')
                        ->label(__('exporter::filament.resources.profile.fields.access_request_status'))
                        ->options([
                            ExporterAccessRequestStatus::PENDING->value => 'Pending',
                            ExporterAccessRequestStatus::APPROVED->value => 'Approved',
                            ExporterAccessRequestStatus::REJECTED->value => 'Rejected',
                        ]),
                    Forms\Components\DateTimePicker::make('access_requested_at')
                        ->label(__('exporter::filament.resources.profile.fields.access_requested_at'))
                        ->disabled(),
                    Forms\Components\Textarea::make('access_request_reason')
                        ->label(__('exporter::filament.resources.profile.fields.access_request_reason'))
                        ->disabled()
                        ->rows(2)
                        ->columnSpanFull(),
                ])
                ->columns(2),

            Forms\Components\Section::make(__('exporter::filament.resources.profile.sections.access'))
                ->schema([
                    Forms\Components\Select::make('exporter_role_id')
                        ->label(__('exporter::filament.resources.profile.fields.role'))
                        ->options(fn () => self::roleOptions())
                        ->placeholder(__('exporter::filament.resources.profile.fields.role_placeholder'))
                        ->helperText(__('exporter::filament.resources.profile.fields.role_help')),
                    Forms\Components\Placeholder::make('is_verified')
                        ->label(__('exporter::filament.resources.profile.fields.is_verified'))
                        ->content(fn (?ExporterProfile $record): string => $record?->is_verified
                            ? __('exporter::filament.resources.profile.fields.verified_yes')
                            : __('exporter::filament.resources.profile.fields.verified_no')),
                    Forms\Components\Placeholder::make('verified_at')
                        ->label(__('exporter::filament.resources.profile.fields.verified_at'))
                        ->content(fn (?ExporterProfile $record): string => $record?->verified_at?->toDateTimeString() ?? '—')
                        ->visible(fn (?ExporterProfile $record) => $record?->is_verified === true),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label(__('exporter::filament.resources.profile.table.user'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('full_name')
                    ->label(__('exporter::filament.resources.profile.table.full_name'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('company_name')
                    ->label(__('exporter::filament.resources.profile.table.company'))
                    ->searchable()
                    ->limit(30),
                Tables\Columns\TextColumn::make('inn')
                    ->label(__('exporter::filament.resources.profile.table.inn'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('access_request_status')
                    ->label(__('exporter::filament.resources.profile.table.access_status'))
                    ->badge()
                    ->color(fn (?ExporterAccessRequestStatus $state): string => match ($state) {
                        ExporterAccessRequestStatus::APPROVED => 'success',
                        ExporterAccessRequestStatus::PENDING => 'warning',
                        ExporterAccessRequestStatus::REJECTED => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?ExporterAccessRequestStatus $state): string => match ($state) {
                        ExporterAccessRequestStatus::APPROVED => 'Approved',
                        ExporterAccessRequestStatus::PENDING => 'Pending',
                        ExporterAccessRequestStatus::REJECTED => 'Rejected',
                        default => 'N/A',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('access_requested_at')
                    ->label(__('exporter::filament.resources.profile.table.requested_at'))
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('role.name')
                    ->label(__('exporter::filament.resources.profile.table.role'))
                    ->badge()
                    ->color(fn (?string $state): string => $state ? 'success' : 'gray')
                    ->default('No Role'),
                Tables\Columns\TextColumn::make('role_status')
                    ->label(__('exporter::filament.resources.profile.table.role_status'))
                    ->getStateUsing(fn (ExporterProfile $record) => self::getRoleStatus($record))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Active' => 'success',
                        'Expired' => 'danger',
                        'Not Started' => 'warning',
                        'Inactive' => 'gray',
                        'No Role' => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\IconColumn::make('is_verified')
                    ->label(__('exporter::filament.resources.profile.table.verified'))
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('exporter::filament.resources.profile.table.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('access_request_status')
                    ->label(__('exporter::filament.resources.profile.filters.access_status'))
                    ->options([
                        ExporterAccessRequestStatus::PENDING->value => 'Pending',
                        ExporterAccessRequestStatus::APPROVED->value => 'Approved',
                        ExporterAccessRequestStatus::REJECTED->value => 'Rejected',
                    ]),
                Tables\Filters\TernaryFilter::make('is_verified')
                    ->label(__('exporter::filament.resources.profile.filters.verified')),
                Tables\Filters\SelectFilter::make('exporter_role_id')
                    ->label(__('exporter::filament.resources.profile.filters.role'))
                    ->options(fn () => self::roleOptions())
                    ->placeholder('All'),
                Tables\Filters\Filter::make('no_role')
                    ->label(__('exporter::filament.resources.profile.filters.no_role'))
                    ->query(fn ($query) => $query->whereNull('exporter_role_id')),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('verify')
                    ->label(__('exporter::filament.resources.profile.actions.verify'))
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(fn (ExporterProfile $record) => $record->verify(auth()->id()))
                    ->visible(fn (ExporterProfile $record) => ! $record->is_verified),
                Tables\Actions\Action::make('approve_access')
                    ->label(__('exporter::filament.resources.profile.actions.approve_access'))
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (ExporterProfile $record): void {
                        $record->update([
                            'access_request_status' => ExporterAccessRequestStatus::APPROVED,
                        ]);
                        self::notifyUser($record, 'approved');
                        Notification::make()
                            ->title('Access approved')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (ExporterProfile $record) => $record->access_request_status === ExporterAccessRequestStatus::PENDING),
                Tables\Actions\Action::make('reject_access')
                    ->label(__('exporter::filament.resources.profile.actions.reject_access'))
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (ExporterProfile $record): void {
                        $record->update([
                            'access_request_status' => ExporterAccessRequestStatus::REJECTED,
                        ]);
                        self::notifyUser($record, 'rejected');
                        Notification::make()
                            ->title('Access rejected')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (ExporterProfile $record) => $record->access_request_status === ExporterAccessRequestStatus::PENDING),
                Tables\Actions\Action::make('assign_role')
                    ->label(__('exporter::filament.resources.profile.actions.assign_role'))
                    ->icon('heroicon-o-shield-check')
                    ->form([
                        Forms\Components\Select::make('exporter_role_id')
                            ->label(__('exporter::filament.resources.profile.fields.role'))
                            ->options(fn () => self::roleOptions())
                            ->required(),
                    ])
                    ->action(function (ExporterProfile $record, array $data): void {
                        $roleId = (int) $data['exporter_role_id'];

                        if (self::blocksUnlimitedRoleAssignment($record, $roleId)) {
                            Notification::make()
                                ->title(__('exporter::filament.resources.profile.notifications.ineligible_for_unlimited_role'))
                                ->danger()
                                ->send();

                            return;
                        }

                        $record->update(['exporter_role_id' => $roleId]);
                    })
                    ->visible(fn (ExporterProfile $record) => $record->exporter_role_id === null),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('bulk_approve_access')
                        ->label(__('exporter::filament.resources.profile.actions.bulk_approve'))
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records): void {
                            $records->each(function (ExporterProfile $record): void {
                                $record->update([
                                    'access_request_status' => ExporterAccessRequestStatus::APPROVED,
                                ]);
                                self::notifyUser($record, 'approved');
                            });
                            Notification::make()
                                ->title($records->count().' profiles approved')
                                ->success()
                                ->send();
                        }),
                    Tables\Actions\BulkAction::make('bulk_reject_access')
                        ->label(__('exporter::filament.resources.profile.actions.bulk_reject'))
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records): void {
                            $records->each(function (ExporterProfile $record): void {
                                $record->update([
                                    'access_request_status' => ExporterAccessRequestStatus::REJECTED,
                                ]);
                                self::notifyUser($record, 'rejected');
                            });
                            Notification::make()
                                ->title($records->count().' profiles rejected')
                                ->success()
                                ->send();
                        }),
                    Tables\Actions\BulkAction::make('bulk_assign_role')
                        ->label(__('exporter::filament.resources.profile.actions.bulk_assign_role'))
                        ->icon('heroicon-o-shield-check')
                        ->form([
                            Forms\Components\Select::make('exporter_role_id')
                                ->label(__('exporter::filament.resources.profile.fields.role'))
                                ->options(fn () => self::roleOptions())
                                ->required(),
                        ])
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data): void {
                            $roleId = (int) $data['exporter_role_id'];
                            $skipped = 0;

                            $records->each(function (ExporterProfile $record) use ($roleId, &$skipped): void {
                                if (self::blocksUnlimitedRoleAssignment($record, $roleId)) {
                                    $skipped++;

                                    return;
                                }

                                $record->update(['exporter_role_id' => $roleId]);
                            });

                            if ($skipped > 0) {
                                Notification::make()
                                    ->title(__('exporter::filament.resources.profile.notifications.bulk_assign_skipped', ['count' => $skipped]))
                                    ->warning()
                                    ->send();
                            }
                        }),
                    Tables\Actions\BulkAction::make('bulk_verify')
                        ->label(__('exporter::filament.resources.profile.actions.bulk_verify'))
                        ->icon('heroicon-o-check-badge')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records): void {
                            $adminId = auth()->id();
                            $records->each(fn (ExporterProfile $record) => $record->verify($adminId));
                        })
                        ->requiresConfirmation(),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListExporterProfiles::route('/'),
            'edit' => Pages\EditExporterProfile::route('/{record}/edit'),
        ];
    }

    private static function roleOptions(): array
    {
        return ExporterRole::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }

    public static function roleIsUnlimited(int $roleId): bool
    {
        return (bool) ExporterRole::query()->whereKey($roleId)->value('is_unlimited');
    }

    public static function blocksUnlimitedRoleAssignment(ExporterProfile $record, int $roleId): bool
    {
        return self::roleIsUnlimited($roleId) && ! $record->isEligibleForUnlimitedRole();
    }

    private static function notifyUser(ExporterProfile $record, string $decision): void
    {
        if (! $record->auth_id) {
            return;
        }

        $titles = [
            'approved' => [
                'uz' => "Eksporter so'rovi tasdiqlandi",
                'ru' => 'Запрос экспортёра одобрен',
                'en' => 'Exporter access approved',
            ],
            'rejected' => [
                'uz' => "Eksporter so'rovi rad etildi",
                'ru' => 'Запрос экспортёра отклонён',
                'en' => 'Exporter access rejected',
            ],
        ];

        $bodies = [
            'approved' => [
                'uz' => "Sizning eksporter platformasiga kirish so'rovingiz tasdiqlandi.",
                'ru' => 'Ваш запрос на доступ к платформе экспортёра одобрен.',
                'en' => 'Your request to access the exporter platform has been approved.',
            ],
            'rejected' => [
                'uz' => "Sizning eksporter platformasiga kirish so'rovingiz rad etildi.",
                'ru' => 'Ваш запрос на доступ к платформе экспортёра отклонён.',
                'en' => 'Your request to access the exporter platform has been rejected.',
            ],
        ];

        event(new RawNotificationEvent(
            authIds: $record->auth_id,
            type: 'exporter.access_request.'.$decision,
            title: $titles[$decision],
            body: $bodies[$decision],
            data: [
                'action' => 'exporter_access',
                'status' => $decision,
            ],
        ));
    }

    private static function getRoleStatus(ExporterProfile $record): string
    {
        $role = $record->role;

        if ($role === null) {
            return 'No Role';
        }

        if (! $role->is_active) {
            return 'Inactive';
        }

        if ($role->isNotStarted()) {
            return 'Not Started';
        }

        if ($role->isExpired()) {
            return 'Expired';
        }

        return 'Active';
    }
}
