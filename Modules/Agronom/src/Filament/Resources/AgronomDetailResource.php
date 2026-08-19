<?php

declare(strict_types=1);

namespace Modules\Agronom\Filament\Resources;

use App\Filament\NavigationGroup;
use App\Traits\HasTranslatedFilamentLabels;
use Filament\Forms;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use Modules\Agronom\Filament\Resources\AgronomDetailResource\Pages;
use Modules\Agronom\Models\AgronomDetail;
use Modules\Agronom\Models\Specialization;
use Modules\Agronom\Support\DefaultAgronomProfileImage;
use Modules\Core\Models\City;
use Modules\Core\Models\Region;

class AgronomDetailResource extends Resource
{
    use HasTranslatedFilamentLabels;

    protected static ?string $model = AgronomDetail::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationLabel = 'Agronoms';

    protected static ?string $navigationGroup = NavigationGroup::ServicesAndJobs->value;

    protected static ?int $navigationSort = 50;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['user.detail.media', 'user.detail.region', 'user.detail.city', 'specializationRelations']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('User Information')
                    ->schema([
                        Forms\Components\TextInput::make('user_id')
                            ->label('User ID')
                            ->numeric()
                            ->required()
                            ->disabled(fn ($record) => $record !== null),
                        Forms\Components\Placeholder::make('user_name')
                            ->label('User')
                            ->content(fn ($record) => $record?->user?->name ?? '-'),
                        Forms\Components\Placeholder::make('user_phone')
                            ->label('Phone')
                            ->content(fn ($record) => $record?->user?->phone ?? '-'),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Profile Image')
                    ->schema([
                        Forms\Components\Placeholder::make('current_profile_image')
                            ->label('Current Photo')
                            ->content(function ($record): HtmlString {
                                $imageUrl = $record?->user_id === null
                                    ? app(DefaultAgronomProfileImage::class)->url()
                                    : ($record?->user?->detail?->image_url
                                        ?? app(DefaultAgronomProfileImage::class)->url());

                                return new HtmlString('<img src="'.$imageUrl.'" class="w-24 h-24 rounded-full object-cover" />');
                            }),
                        Forms\Components\FileUpload::make('_profile_image')
                            ->label('Upload New Profile Image')
                            ->image()
                            ->imageResizeMode('cover')
                            ->imageCropAspectRatio('1:1')
                            ->imageResizeTargetWidth('400')
                            ->imageResizeTargetHeight('400')
                            ->maxSize(5120)
                            ->helperText('Upload a new profile image (max 5MB). Leave empty to keep current image.')
                            ->dehydrated(false)
                            ->visible(fn ($record): bool => $record?->user_id !== null),
                    ])
                    ->visible(fn ($record) => $record !== null),

                Forms\Components\Section::make('Location')
                    ->schema([
                        Forms\Components\Select::make('_region_id')
                            ->label('Region')
                            ->options(fn () => Region::query()->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(fn (Forms\Set $set) => $set('_city_id', null))
                            ->dehydrated(false),
                        Forms\Components\Select::make('_city_id')
                            ->label('City')
                            ->options(fn (Forms\Get $get) => City::query()
                                ->when($get('_region_id'), fn ($q, $regionId) => $q->where('region_id', $regionId))
                                ->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('_address')
                            ->label('Address')
                            ->maxLength(255)
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('_latitude')
                            ->label('Latitude')
                            ->numeric()
                            ->step(0.0000001)
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('_longitude')
                            ->label('Longitude')
                            ->numeric()
                            ->step(0.0000001)
                            ->dehydrated(false),
                    ])
                    ->columns(3)
                    ->visible(fn ($record) => $record !== null),

                Forms\Components\Section::make('Bio')
                    ->schema([
                        Forms\Components\Textarea::make('bio.uz')
                            ->label('Bio (Uzbek)')
                            ->rows(3)
                            ->maxLength(5000),
                        Forms\Components\Textarea::make('bio.en')
                            ->label('Bio (English)')
                            ->rows(3)
                            ->maxLength(5000),
                        Forms\Components\Textarea::make('bio.ru')
                            ->label('Bio (Russian)')
                            ->rows(3)
                            ->maxLength(5000),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Professional Details')
                    ->schema([
                        Forms\Components\TextInput::make('years_of_experience')
                            ->label('Years of Experience')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100),
                        Forms\Components\Select::make('specializationRelations')
                            ->label('Specializations')
                            ->relationship('specializationRelations', 'name')
                            ->options(fn (): array => self::specializationOptions())
                            ->multiple()
                            ->preload()
                            ->searchable(),
                        Forms\Components\TextInput::make('price')
                            ->label('Consultation Price (UZS)')
                            ->numeric()
                            ->minValue(50_000)
                            ->maxValue(500_000)
                            ->step(10_000)
                            ->required()
                            ->visible(fn ($record): bool => $record?->user_id === null)
                            ->helperText('Used only for unregistered agronomists.'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Certificates')
                    ->schema([
                        SpatieMediaLibraryFileUpload::make('certificates')
                            ->collection(AgronomDetail::MEDIA_COLLECTION_CERTIFICATES)
                            ->label('Certificates')
                            ->image()
                            ->multiple()
                            ->reorderable()
                            ->visibility('public')
                            ->maxSize(10240)
                            ->maxFiles(10)
                            ->helperText('Upload certificates (max 10 files, 10MB each)'),
                    ]),

                Forms\Components\Section::make('Rating & Stats')
                    ->schema([
                        Forms\Components\TextInput::make('rating')
                            ->label('Rating')
                            ->numeric()
                            ->step(0.01)
                            ->minValue(0)
                            ->maxValue(5)
                            ->disabled(),
                        Forms\Components\TextInput::make('total_ratings')
                            ->label('Total Ratings')
                            ->numeric()
                            ->disabled(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('user.detail.profile_media_url')
                    ->label('Photo')
                    ->circular()
                    ->defaultImageUrl(fn (): string => app(DefaultAgronomProfileImage::class)->url()),
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.phone')
                    ->label('Phone')
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.detail.region.name')
                    ->label('Region')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('user.detail.city.name')
                    ->label('City')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('years_of_experience')
                    ->label('Experience')
                    ->suffix(' years')
                    ->sortable(),
                Tables\Columns\TextColumn::make('price')
                    ->label('Price')
                    ->money('UZS', divideBy: 1)
                    ->placeholder('-')
                    ->sortable(),
                Tables\Columns\TextColumn::make('rating')
                    ->label('Rating')
                    ->numeric(decimalPlaces: 2)
                    ->sortable()
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        $state >= 4.5 => 'success',
                        $state >= 3.5 => 'info',
                        $state >= 2.5 => 'warning',
                        default => 'danger',
                    }),
                Tables\Columns\TextColumn::make('total_ratings')
                    ->label('Reviews')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('agronomServices_count')
                    ->label('Services')
                    ->counts('agronomServices')
                    ->sortable(),
                Tables\Columns\TextColumn::make('agronomRequests_count')
                    ->label('Requests')
                    ->counts('agronomRequests')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Joined')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('has_rating')
                    ->label('Has Rating')
                    ->query(fn (Builder $query) => $query->where('rating', '>', 0)),
                Tables\Filters\Filter::make('high_rating')
                    ->label('High Rating (4+)')
                    ->query(fn (Builder $query) => $query->where('rating', '>=', 4)),
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

    /**
     * @return array<string, array<int, string>>
     */
    private static function specializationOptions(): array
    {
        $locale = app()->getLocale();
        $groupLabels = match ($locale) {
            'uz' => ['expertise' => 'Umumiy mutaxassislik', 'crop' => 'Ekin bo\'yicha mutaxassislik'],
            'ru' => ['expertise' => 'Общая специализация', 'crop' => 'Специализация по культурам'],
            default => ['expertise' => 'General expertise', 'crop' => 'Crop expertise'],
        };
        $options = [];

        foreach (Specialization::query()->active()->ordered()->get() as $specialization) {
            $category = (string) $specialization->category;
            $group = $groupLabels[$category] ?? ucfirst($category);
            $options[$group][$specialization->id] = $specialization->getTranslation('name', $locale);
        }

        return $options;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAgronomDetails::route('/'),
            'create' => Pages\CreateAgronomDetail::route('/create'),
            'edit' => Pages\EditAgronomDetail::route('/{record}/edit'),
        ];
    }
}
