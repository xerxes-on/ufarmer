<?php

declare(strict_types=1);

namespace Modules\General\Filament\Resources;

use App\Filament\NavigationGroup;
use App\Traits\HasTranslatedFilamentLabels;
use Filament\Forms;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Schema;
use Modules\Crops\Models\Crop;
use Modules\General\Filament\Resources\ArticleResource\Pages;
use Modules\General\Models\Article;
use Modules\General\Rules\NoContentCorruption;

class ArticleResource extends Resource
{
    use HasTranslatedFilamentLabels;

    protected static ?string $model = Article::class;

    protected static ?string $navigationIcon = 'heroicon-o-newspaper';

    protected static ?string $navigationGroup = NavigationGroup::Content->value;

    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Tabs::make(__('general::filament.resources.article.tabs.content'))
                ->tabs(static::contentTabs())
                ->columnSpanFull(),
            Forms\Components\Section::make(__('general::filament.resources.article.sections.media'))
                ->schema([
                    SpatieMediaLibraryFileUpload::make('preview_image')
                        ->collection(Article::COLLECTION_PREVIEW)
                        ->label(__('general::filament.resources.article.fields.preview_image'))
                        ->image()
                        ->required()
                        ->visibility('public')
                        ->preserveFilenames()
                        ->imageResizeMode('contain')
                        ->imageCropAspectRatio('16:9'),
                ])
                ->columns(1),
            Forms\Components\Section::make(__('general::filament.resources.article.sections.crops'))
                ->schema([
                    Forms\Components\MultiSelect::make('crop_ids')
                        ->label(__('general::filament.resources.article.fields.crops'))
                        ->options(fn () => static::cropOptions())
                        ->placeholder(__('general::filament.resources.article.placeholders.crops'))
                        ->default([])
                        ->afterStateHydrated(function (Forms\Components\MultiSelect $component, $state): void {
                            $component->state(array_map('strval', (array) $state));
                        })
                        ->dehydrateStateUsing(function (?array $state) {
                            return collect($state)->map(fn ($value) => (int) $value)->filter()->values()->all();
                        })
                        ->columnSpanFull(),
                ]),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        $columns = [
            ...static::titleColumns(),
            Tables\Columns\TextColumn::make('crops_display')
                ->label(__('general::filament.resources.article.table.columns.crops'))
                ->badge()
                ->getStateUsing(fn (Article $record) => static::formatCrops($record))
                ->toggleable(),
            Tables\Columns\TextColumn::make('updated_at')
                ->label(__('general::filament.resources.article.table.columns.updated_at'))
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ];

        return $table
            ->columns($columns)
            ->filters([
                Tables\Filters\SelectFilter::make('crop')
                    ->label(__('general::filament.resources.article.filters.crop'))
                    ->options(fn () => static::cropOptions())
                    ->query(function ($query, array $data) {
                        $value = $data['value'] ?? null;

                        if (blank($value)) {
                            return $query;
                        }

                        return $query->whereJsonContains('crop_ids', (int) $value);
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    private static function contentTabs(): array
    {
        $noCorruption = new NoContentCorruption;

        return collect(static::availableLocales())
            ->map(static function (string $locale) use ($noCorruption): Tab {
                return Tab::make(static::localeLabel($locale))
                    ->schema([
                        Forms\Components\TextInput::make("title_{$locale}")
                            ->label(__('general::filament.resources.article.fields.title'))
                            ->maxLength(255)
                            ->required()
                            ->rules([$noCorruption]),
                        Forms\Components\Textarea::make("preview_{$locale}")
                            ->label(__('general::filament.resources.article.fields.preview'))
                            ->rows(2)
                            ->rules([$noCorruption]),
                        Forms\Components\MarkdownEditor::make("body_{$locale}")
                            ->label(__('general::filament.resources.article.fields.body'))
                            ->columnSpanFull()
                            ->rules([$noCorruption]),
                    ])
                    ->columns(1);
            })
            ->values()
            ->all();
    }

    private static function availableLocales(): array
    {
        static $locales;

        if ($locales !== null) {
            return $locales;
        }

        $supported = config('app.supported_locales', []);
        $configuredLocales = array_is_list($supported) ? $supported : array_keys($supported);

        $table = (new Article)->getTable();
        $columns = Schema::getColumnListing($table);

        $locales = collect($configuredLocales)
            ->filter(static function (string $locale) use ($columns): bool {
                return in_array("title_{$locale}", $columns, true)
                    && in_array("preview_{$locale}", $columns, true)
                    && in_array("body_{$locale}", $columns, true);
            })
            ->values()
            ->all();

        return $locales;
    }

    private static function localeLabel(string $locale): string
    {
        $key = "general::filament.locales.{$locale}";
        $label = __($key);

        return $label === $key ? strtoupper($locale) : $label;
    }

    private static function primaryLocale(): string
    {
        $locales = static::availableLocales();

        return $locales[0] ?? app()->getLocale();
    }

    private static function titleColumns(): array
    {
        $primaryLocale = static::primaryLocale();

        return collect(static::availableLocales())
            ->map(static function (string $locale) use ($primaryLocale) {
                return Tables\Columns\TextColumn::make("title_{$locale}")
                    ->label(__('general::filament.resources.article.table.columns.title', ['locale' => strtoupper($locale)]))
                    ->limit(50)
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: $locale !== $primaryLocale);
            })
            ->values()
            ->all();
    }

    private static function cropOptions(): array
    {
        $locale = app()->getLocale();

        return Crop::query()
            ->orderBy('id')
            ->get()
            ->mapWithKeys(static function (Crop $crop) use ($locale): array {
                $name = $crop->getTranslation('name', $locale, false);

                if (! filled($name)) {
                    $name = __('general::filament.resources.article.table.columns.crop_fallback', ['id' => $crop->id]);
                }

                return [$crop->id => $name];
            })
            ->toArray();
    }

    private static function formatCrops(Article $record): string
    {
        $locale = app()->getLocale();

        $names = $record->crops->map(static function (Crop $crop) use ($locale): ?string {
            $name = $crop->getTranslation('name', $locale, false);

            if (! filled($name)) {
                return __('general::filament.resources.article.table.columns.crop_fallback', ['id' => $crop->id]);
            }

            return $name;
        })->filter(static fn (?string $value): bool => filled($value));

        if ($names->isEmpty()) {
            return __('general::filament.resources.article.table.columns.general');
        }

        return $names->implode(', ');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListArticles::route('/'),
            'create' => Pages\CreateArticle::route('/create'),
            'edit' => Pages\EditArticle::route('/{record}/edit'),
        ];
    }
}
