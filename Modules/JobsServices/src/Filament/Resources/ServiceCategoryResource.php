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
use Illuminate\Validation\Rules\Unique;
use Modules\JobsServices\Filament\Resources\ServiceCategoryResource\Pages;
use Modules\JobsServices\Models\ServiceCategory;

class ServiceCategoryResource extends Resource
{
    use HasTranslatedFilamentLabels;

    protected static ?string $model = ServiceCategory::class;

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?string $navigationLabel = 'Service Categories';

    protected static ?string $navigationGroup = NavigationGroup::ServicesAndJobs->value;

    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        Forms\Components\TextInput::make('name.uz')
                            ->label('Name (Uzbek)')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('name.ru')
                            ->label('Name (Russian)')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('name.en')
                            ->label('Name (English)')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('icon')
                            ->label('Icon (Emoji)')
                            ->maxLength(10)
                            ->helperText('Enter an emoji icon (e.g., 🚜, 🧴, 👨‍🌾)'),
                        Forms\Components\Select::make('applies_to')
                            ->label('Applies To')
                            ->required()
                            ->options([
                                'offer' => 'Service Offers only',
                                'announcement' => 'Job Announcements only',
                                'both' => 'Both',
                            ])
                            ->default('both'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Images')
                    ->schema([
                        SpatieMediaLibraryFileUpload::make('image')
                            ->collection(ServiceCategory::MEDIA_COLLECTION_IMAGE)
                            ->label('Detail Image (Square)')
                            ->image()
                            ->visibility('public')
                            ->maxSize(10240)
                            ->helperText('Default square image shown on the service detail screen (max 10MB).'),
                        SpatieMediaLibraryFileUpload::make('banner_image')
                            ->collection(ServiceCategory::MEDIA_COLLECTION_BANNER)
                            ->label('Default Offer Banner')
                            ->image()
                            ->visibility('public')
                            ->maxSize(10240)
                            ->helperText('Wide image used on offer cards when an offer has no custom banner. Recommended ratio: 16:9 (max 10MB).'),
                        SpatieMediaLibraryFileUpload::make('icon_image')
                            ->collection(ServiceCategory::MEDIA_COLLECTION_ICON)
                            ->label('Category Icon Image')
                            ->image()
                            ->visibility('public')
                            ->maxSize(5120)
                            ->helperText('Upload an icon image (max 5MB)'),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Settings')
                    ->schema([
                        Forms\Components\TextInput::make('sort_order')
                            ->label('Sort Order')
                            ->integer()
                            ->minValue(0)
                            ->default(fn (): int => ((int) ServiceCategory::active()->max('sort_order')) + 1)
                            ->unique(
                                table: ServiceCategory::class,
                                column: 'sort_order',
                                ignoreRecord: true,
                                modifyRuleUsing: fn (Unique $rule) => $rule->where('is_active', true)->whereNull('deleted_at'),
                            )
                            ->helperText('Must be unique among active categories.'),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image')
                    ->label('Image')
                    ->circular()
                    ->getStateUsing(fn (ServiceCategory $record) => $record->getFirstMediaUrl(ServiceCategory::MEDIA_COLLECTION_IMAGE) ?: null),
                Tables\Columns\TextColumn::make('icon')
                    ->label('Icon')
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->getStateUsing(fn ($record) => $record->getTranslation('name', 'uz'))
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where('name->uz', 'like', "%{$search}%")
                            ->orWhere('name->en', 'like', "%{$search}%")
                            ->orWhere('name->ru', 'like', "%{$search}%");
                    })
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderByRaw("name->>'uz' {$direction}");
                    }),
                Tables\Columns\TextColumn::make('services_count')
                    ->label('Services')
                    ->counts('services')
                    ->sortable(),
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Order')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Active')
                    ->beforeStateUpdated(function (bool $state, ServiceCategory $record): void {
                        if (! $state) {
                            return;
                        }

                        $collides = ServiceCategory::active()
                            ->where('sort_order', $record->sort_order)
                            ->whereKeyNot($record->getKey())
                            ->exists();

                        if ($collides) {
                            $record->update(['sort_order' => ((int) ServiceCategory::active()->max('sort_order')) + 1]);
                        }
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->label('Archive')
                    ->icon('heroicon-o-archive-box')
                    ->modalHeading('Archive service category')
                    ->modalDescription('This category will be hidden from Active and Inactive lists. It is not permanently deleted.')
                    ->modalSubmitActionLabel('Archive')
                    ->successNotificationTitle('Archived'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Archive selected')
                        ->icon('heroicon-o-archive-box')
                        ->successNotificationTitle('Archived'),
                ]),
            ])
            ->reorderable('sort_order')
            ->defaultSort('sort_order');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServiceCategories::route('/'),
            'create' => Pages\CreateServiceCategory::route('/create'),
            'edit' => Pages\EditServiceCategory::route('/{record}/edit'),
        ];
    }
}
