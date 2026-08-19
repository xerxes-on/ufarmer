<?php

declare(strict_types=1);

namespace Modules\JobsServices\Filament\Resources;

use App\Filament\NavigationGroup;
use App\Traits\HasTranslatedFilamentLabels;
use Filament\Forms;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Core\Enums\Currency;
use Modules\Core\Enums\PropertyUnit;
use Modules\JobsServices\Filament\Resources\JobAnnouncementResource\Pages;
use Modules\JobsServices\Models\JobAnnouncement;
use Modules\JobsServices\Models\ServiceCategory;

class JobAnnouncementResource extends Resource
{
    use HasTranslatedFilamentLabels;

    protected static ?string $model = JobAnnouncement::class;

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';

    protected static ?string $navigationLabel = 'Job Announcements';

    protected static ?string $navigationGroup = NavigationGroup::ServicesAndJobs->value;

    protected static ?int $navigationSort = 20;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Title')
                    ->schema([
                        Forms\Components\TextInput::make('title.uz')
                            ->label('Title (Uzbek)')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('title.en')
                            ->label('Title (English)')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('title.ru')
                            ->label('Title (Russian)')
                            ->maxLength(255),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Description')
                    ->schema([
                        Forms\Components\Textarea::make('description.uz')
                            ->label('Description (Uzbek)')
                            ->rows(3)
                            ->maxLength(5000),
                        Forms\Components\Textarea::make('description.en')
                            ->label('Description (English)')
                            ->rows(3)
                            ->maxLength(5000),
                        Forms\Components\Textarea::make('description.ru')
                            ->label('Description (Russian)')
                            ->rows(3)
                            ->maxLength(5000),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Category & User')
                    ->schema([
                        Forms\Components\Select::make('category_id')
                            ->label('Category')
                            ->options(ServiceCategory::query()->active()->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\TextInput::make('user_id')
                            ->label('User ID')
                            ->numeric()
                            ->required(),
                        Forms\Components\TextInput::make('executor_id')
                            ->label('Executor ID')
                            ->numeric()
                            ->nullable(),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Pricing & Property')
                    ->schema([
                        Forms\Components\TextInput::make('price')
                            ->label('Price')
                            ->numeric()
                            ->prefix('UZS'),
                        Forms\Components\Select::make('currency')
                            ->label('Currency')
                            ->options(Currency::options())
                            ->default(Currency::UZS->value),
                        Forms\Components\TextInput::make('property_size')
                            ->label('Property Size')
                            ->numeric(),
                        Forms\Components\Select::make('property_unit')
                            ->label('Property Unit')
                            ->options(PropertyUnit::options()),
                    ])
                    ->columns(4),

                Forms\Components\Section::make('Location')
                    ->schema([
                        Forms\Components\TextInput::make('latitude')
                            ->label('Latitude')
                            ->numeric()
                            ->step(0.0000001)
                            ->required(),
                        Forms\Components\TextInput::make('longitude')
                            ->label('Longitude')
                            ->numeric()
                            ->step(0.0000001)
                            ->required(),
                        Forms\Components\TextInput::make('address')
                            ->label('Address')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('city')
                            ->label('City')
                            ->maxLength(120),
                        Forms\Components\TextInput::make('region')
                            ->label('Region')
                            ->maxLength(120),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Timing')
                    ->schema([
                        Forms\Components\DateTimePicker::make('deadline')
                            ->label('Deadline'),
                        Forms\Components\DateTimePicker::make('fixed_time')
                            ->label('Fixed Time'),
                        Forms\Components\DateTimePicker::make('started_at')
                            ->label('Started At'),
                        Forms\Components\DateTimePicker::make('finished_at')
                            ->label('Finished At'),
                    ])
                    ->columns(4),

                Forms\Components\Section::make('Images')
                    ->schema([
                        SpatieMediaLibraryFileUpload::make('images')
                            ->collection(JobAnnouncement::MEDIA_COLLECTION_IMAGES)
                            ->label('Job Images')
                            ->image()
                            ->multiple()
                            ->reorderable()
                            ->visibility('public')
                            ->maxSize(10240)
                            ->maxFiles(5)
                            ->helperText('Upload up to 5 images (max 10MB each)'),
                    ]),

                Forms\Components\Section::make('Status & Stats')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'open' => 'Open',
                                'in_progress' => 'In Progress',
                                'completed' => 'Completed',
                                'cancelled' => 'Cancelled',
                            ])
                            ->default('open')
                            ->required(),
                        Forms\Components\TextInput::make('views_count')
                            ->label('Views Count')
                            ->numeric()
                            ->default(0)
                            ->disabled(),
                        Forms\Components\TextInput::make('applications_count')
                            ->label('Applications Count')
                            ->numeric()
                            ->default(0)
                            ->disabled(),
                    ])
                    ->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image')
                    ->label('Image')
                    ->circular()
                    ->getStateUsing(fn (JobAnnouncement $record) => $record->getFirstMediaUrl(JobAnnouncement::MEDIA_COLLECTION_IMAGES) ?: null),
                Tables\Columns\TextColumn::make('title')
                    ->label('Title')
                    ->getStateUsing(fn ($record) => $record->getTitleTranslations()['uz'] ?? $record->getTitleTranslations()['en'] ?? '-')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where('title->uz', 'ilike', "%{$search}%")
                            ->orWhere('title->en', 'ilike', "%{$search}%")
                            ->orWhere('title->ru', 'ilike', "%{$search}%");
                    })
                    ->limit(50),
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Category')
                    ->sortable(),
                Tables\Columns\TextColumn::make('price')
                    ->label('Price')
                    ->money('UZS')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'open' => 'success',
                        'in_progress' => 'info',
                        'completed' => 'gray',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('city')
                    ->label('City')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('views_count')
                    ->label('Views')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('applications_count')
                    ->label('Applications')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('deadline')
                    ->label('Deadline')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'open' => 'Open',
                        'in_progress' => 'In Progress',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ]),
                Tables\Filters\SelectFilter::make('category_id')
                    ->label('Category')
                    ->options(ServiceCategory::query()->active()->pluck('name', 'id'))
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListJobAnnouncements::route('/'),
            'create' => Pages\CreateJobAnnouncement::route('/create'),
            'edit' => Pages\EditJobAnnouncement::route('/{record}/edit'),
        ];
    }
}
