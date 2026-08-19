<?php

declare(strict_types=1);

namespace Modules\Core\Filament\Resources;

use App\Filament\NavigationGroup;
use App\Traits\HasTranslatedFilamentLabels;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Core\Filament\Resources\UserResource\Pages;
use Modules\Core\Filament\Resources\UserResource\RelationManagers\AgronomServicesRelationManager;
use Modules\Core\Filament\Resources\UserResource\RelationManagers\AreasRelationManager;
use Modules\Core\Filament\Resources\UserResource\RelationManagers\ServiceOffersRelationManager;
use Modules\Core\Filament\Resources\UserResource\RelationManagers\ServiceRequestsRelationManager;
use Modules\Core\Models\User;

class UserResource extends Resource
{
    use HasTranslatedFilamentLabels;

    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Users';

    protected static ?string $navigationGroup = NavigationGroup::Administration->value;

    protected static ?int $navigationSort = 10;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with([
            'detail.region',
            'detail.city',
            'myIdIdentity',
            'agronomDetail.specializationRelations',
            'exporterProfile.role',
            'workerProfile',
        ]);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('User Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Name')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('phone')
                            ->label('Phone')
                            ->tel()
                            ->maxLength(20),
                        Forms\Components\TextInput::make('auth_id')
                            ->label('Auth ID')
                            ->numeric()
                            ->disabled(),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Verification')
                    ->schema([
                        Forms\Components\Toggle::make('myid_verified')
                            ->label('MyID Verified'),
                        Forms\Components\DateTimePicker::make('myid_verified_at')
                            ->label('Verified At'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('User Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('id')
                            ->label('User ID'),
                        Infolists\Components\TextEntry::make('name')
                            ->label('Name'),
                        Infolists\Components\TextEntry::make('first_name')
                            ->label('First Name')
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('last_name')
                            ->label('Last Name')
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('surname')
                            ->label('Surname')
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('phone')
                            ->label('Phone'),
                        Infolists\Components\TextEntry::make('email')
                            ->label('Email')
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('auth_id')
                            ->label('Auth ID'),
                        Infolists\Components\TextEntry::make('entity_id')
                            ->label('Entity ID')
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('application_alias')
                            ->label('Application')
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('application_id')
                            ->label('Application ID')
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('lang')
                            ->label('Legacy Language')
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('language')
                            ->label('Language')
                            ->placeholder('-'),
                        Infolists\Components\IconEntry::make('is_online')
                            ->label('Online')
                            ->boolean(),
                        Infolists\Components\TextEntry::make('last_seen_at')
                            ->label('Last Seen')
                            ->dateTime()
                            ->placeholder('-'),
                        Infolists\Components\IconEntry::make('myid_verified')
                            ->label('MyID Verified')
                            ->boolean(),
                        Infolists\Components\TextEntry::make('myid_verified_at')
                            ->label('Verified At')
                            ->dateTime()
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Joined')
                            ->dateTime(),
                        Infolists\Components\TextEntry::make('updated_at')
                            ->label('Updated')
                            ->dateTime(),
                    ])
                    ->columns(4),

                Infolists\Components\Section::make('Profile Details')
                    ->schema([
                        Infolists\Components\ImageEntry::make('detail.image_url')
                            ->label('Photo')
                            ->circular(),
                        Infolists\Components\TextEntry::make('detail.birth_date')
                            ->label('Birth Date')
                            ->date()
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('detail.region.localized_name')
                            ->label('Region')
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('detail.city.localized_name')
                            ->label('City')
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('detail.address')
                            ->label('Address')
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('detail.latitude')
                            ->label('Latitude')
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('detail.longitude')
                            ->label('Longitude')
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('detail.experience_years')
                            ->label('Experience')
                            ->suffix(' years')
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('detail.certifications_count')
                            ->label('Certifications')
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('detail.language')
                            ->label('Profile Language')
                            ->placeholder('-'),
                    ])
                    ->columns(3)
                    ->visible(fn (User $record): bool => $record->detail !== null),

                Infolists\Components\Section::make('MyID Identity')
                    ->schema([
                        Infolists\Components\TextEntry::make('myIdIdentity.provider')
                            ->label('Provider'),
                        Infolists\Components\TextEntry::make('myIdIdentity.provider_reference')
                            ->label('Provider Reference')
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('myIdIdentity.verification_status')
                            ->label('Status')
                            ->badge(),
                        Infolists\Components\TextEntry::make('myIdIdentity.verified_at')
                            ->label('Verified At')
                            ->dateTime()
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('myIdIdentity.pinfl')
                            ->label('PINFL')
                            ->copyable()
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('myIdIdentity.document_type')
                            ->label('Document Type')
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('myIdIdentity.passport_series')
                            ->label('Passport Series')
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('myIdIdentity.passport_number')
                            ->label('Passport Number')
                            ->copyable()
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('myIdIdentity.first_name')
                            ->label('First Name')
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('myIdIdentity.last_name')
                            ->label('Last Name')
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('myIdIdentity.middle_name')
                            ->label('Middle Name')
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('myIdIdentity.gender')
                            ->label('Gender')
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('myIdIdentity.birth_date')
                            ->label('Birth Date')
                            ->date()
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('myIdIdentity.birth_place')
                            ->label('Birth Place')
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('myIdIdentity.citizenship')
                            ->label('Citizenship')
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('myIdIdentity.issued_by')
                            ->label('Issued By')
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('myIdIdentity.issued_at')
                            ->label('Issued At')
                            ->date()
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('myIdIdentity.expires_at')
                            ->label('Expires At')
                            ->date()
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('myIdIdentity.permanent_address')
                            ->label('Permanent Address')
                            ->placeholder('-')
                            ->columnSpan(2),
                        Infolists\Components\TextEntry::make('myIdIdentity.temporary_address')
                            ->label('Temporary Address')
                            ->placeholder('-')
                            ->columnSpan(2),
                        Infolists\Components\KeyValueEntry::make('myIdIdentity.metadata')
                            ->label('Metadata')
                            ->columnSpanFull(),
                    ])
                    ->columns(4)
                    ->collapsible()
                    ->visible(fn (User $record): bool => $record->myIdIdentity !== null),

                Infolists\Components\Section::make('MyID Raw Payload')
                    ->schema([
                        Infolists\Components\TextEntry::make('myid_payload')
                            ->label('')
                            ->state(fn (User $record): string => json_encode(
                                $record->myIdIdentity?->payload ?? [],
                                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
                            ) ?: '{}')
                            ->extraAttributes(['class' => 'whitespace-pre-wrap font-mono text-xs'])
                            ->columnSpanFull(),
                    ])
                    ->collapsed()
                    ->collapsible()
                    ->visible(fn (User $record): bool => $record->myIdIdentity !== null),

                Infolists\Components\Section::make('Agronom Profile')
                    ->schema([
                        Infolists\Components\TextEntry::make('agronomDetail.full_name')
                            ->label('Full Name')
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('agronomDetail.phone_number')
                            ->label('Phone')
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('agronomDetail.years_of_experience')
                            ->label('Experience')
                            ->suffix(' years')
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('agronomDetail.education')
                            ->label('Education')
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('agronomDetail.rating')
                            ->label('Rating')
                            ->badge()
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('agronomDetail.total_ratings')
                            ->label('Ratings Count')
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('agronomDetail.price')
                            ->label('Price')
                            ->money('UZS')
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('agronomDetail.specializations')
                            ->label('Specializations')
                            ->listWithLineBreaks()
                            ->bulleted()
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('agronom_bio')
                            ->label('Bio')
                            ->state(fn (User $record): string => self::localizedJson($record->agronomDetail?->getRawOriginal('bio')))
                            ->placeholder('-')
                            ->columnSpanFull(),
                        Infolists\Components\TextEntry::make('agronomDetail.latitude')
                            ->label('Latitude')
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('agronomDetail.longitude')
                            ->label('Longitude')
                            ->placeholder('-'),
                    ])
                    ->columns(4)
                    ->collapsible()
                    ->visible(fn (User $record): bool => $record->agronomDetail !== null),

                Infolists\Components\Section::make('Exporter Profile')
                    ->schema([
                        Infolists\Components\TextEntry::make('exporterProfile.full_name')
                            ->label('Full Name')
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('exporterProfile.position')
                            ->label('Position')
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('exporterProfile.company_name')
                            ->label('Company')
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('exporterProfile.inn')
                            ->label('INN')
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('exporterProfile.license_number')
                            ->label('License Number')
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('exporterProfile.role.name')
                            ->label('Role')
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('exporterProfile.access_request_status')
                            ->label('Access Status')
                            ->badge()
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('exporterProfile.access_requested_at')
                            ->label('Requested At')
                            ->dateTime()
                            ->placeholder('-'),
                        Infolists\Components\IconEntry::make('exporterProfile.is_verified')
                            ->label('Verified')
                            ->boolean(),
                        Infolists\Components\TextEntry::make('exporterProfile.verified_at')
                            ->label('Verified At')
                            ->dateTime()
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('exporterProfile.interested_crops')
                            ->label('Interested Crops')
                            ->listWithLineBreaks()
                            ->bulleted()
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('exporterProfile.interested_regions')
                            ->label('Interested Regions')
                            ->listWithLineBreaks()
                            ->bulleted()
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('exporterProfile.bio')
                            ->label('Bio')
                            ->placeholder('-')
                            ->columnSpan(2),
                        Infolists\Components\TextEntry::make('exporterProfile.access_request_reason')
                            ->label('Access Request Reason')
                            ->placeholder('-')
                            ->columnSpan(2),
                    ])
                    ->columns(4)
                    ->collapsible()
                    ->visible(fn (User $record): bool => $record->exporterProfile !== null),

                Infolists\Components\Section::make('Service Worker Profile')
                    ->schema([
                        Infolists\Components\TextEntry::make('workerProfile.bio')
                            ->label('Bio')
                            ->placeholder('-')
                            ->columnSpan(2),
                        Infolists\Components\TextEntry::make('workerProfile.experience_years')
                            ->label('Experience')
                            ->suffix(' years')
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('workerProfile.specializations')
                            ->label('Specializations')
                            ->listWithLineBreaks()
                            ->bulleted()
                            ->placeholder('-'),
                        Infolists\Components\IconEntry::make('workerProfile.is_available')
                            ->label('Available')
                            ->boolean(),
                        Infolists\Components\TextEntry::make('workerProfile.rating_average')
                            ->label('Average Rating')
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('workerProfile.rating_total')
                            ->label('Rating Total')
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('workerProfile.reviews_count')
                            ->label('Reviews')
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('workerProfile.avatar')
                            ->label('Avatar')
                            ->url(fn (?string $state): ?string => $state)
                            ->openUrlInNewTab()
                            ->placeholder('-'),
                    ])
                    ->columns(4)
                    ->collapsible()
                    ->visible(fn (User $record): bool => $record->workerProfile !== null),

                Infolists\Components\Section::make('Statistics')
                    ->schema([
                        Infolists\Components\TextEntry::make('areas_count')
                            ->label('Areas')
                            ->state(fn ($record) => $record->areas()->count()),
                        Infolists\Components\TextEntry::make('crops_count')
                            ->label('Crops')
                            ->state(fn ($record) => $record->crops()->count()),
                        Infolists\Components\TextEntry::make('agronom_services_count')
                            ->label('Agronom Services')
                            ->state(fn (User $record): int => $record->agronomServices()->count()),
                        Infolists\Components\TextEntry::make('service_offers_count')
                            ->label('Service Offers')
                            ->state(fn (User $record): int => $record->serviceOffers()->count()),
                        Infolists\Components\TextEntry::make('service_requests_count')
                            ->label('Service Requests')
                            ->state(fn (User $record): int => $record->serviceRequests()->count()),
                    ])
                    ->columns(5),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Phone')
                    ->searchable(),
                Tables\Columns\TextColumn::make('auth_id')
                    ->label('Auth ID')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\IconColumn::make('myid_verified')
                    ->label('Verified')
                    ->boolean(),
                Tables\Columns\TextColumn::make('areas_count')
                    ->label('Areas')
                    ->counts('areas')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('crops_count')
                    ->label('Crops')
                    ->counts('crops')
                    ->badge()
                    ->color('success'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Joined')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('myid_verified')
                    ->label('MyID Verified')
                    ->boolean()
                    ->trueLabel('Verified only')
                    ->falseLabel('Unverified only')
                    ->native(false),
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
        return [
            AreasRelationManager::class,
            AgronomServicesRelationManager::class,
            ServiceOffersRelationManager::class,
            ServiceRequestsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'view' => Pages\ViewUser::route('/{record}'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    private static function localizedJson(mixed $value): string
    {
        $translations = is_array($value) ? $value : json_decode((string) $value, true);

        if (! is_array($translations)) {
            return is_string($value) ? $value : '-';
        }

        foreach ([app()->getLocale(), 'uz', 'ru', 'en'] as $locale) {
            $translation = $translations[$locale] ?? null;

            if (is_string($translation) && trim($translation) !== '') {
                return $translation;
            }
        }

        return '-';
    }
}
