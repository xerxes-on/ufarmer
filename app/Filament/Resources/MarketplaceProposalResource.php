<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\NavigationGroup;
use App\Filament\Resources\MarketplaceProposalResource\Pages;
use App\Models\MarketplaceProposal;
use App\Services\ProposalApprovalService;
use App\Traits\HasTranslatedFilamentLabels;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MarketplaceProposalResource extends Resource
{
    use HasTranslatedFilamentLabels;

    protected static ?string $model = MarketplaceProposal::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-check';

    protected static ?string $navigationLabel = 'Product Proposals';

    protected static ?string $modelLabel = 'Product Proposal';

    protected static ?string $pluralModelLabel = 'Product Proposals';

    protected static ?string $navigationGroup = NavigationGroup::MarketplaceAndPrices->value;

    protected static ?int $navigationSort = 10;

    protected static function translationKey(): string
    {
        return 'marketplace_proposal';
    }

    /**
     * @return array<string, string>
     */
    private static function statusOptions(): array
    {
        return [
            'pending' => __('admin-panel.resources.marketplace_proposal.fields.status_pending'),
            'approved' => __('admin-panel.resources.marketplace_proposal.fields.status_approved'),
            'rejected' => __('admin-panel.resources.marketplace_proposal.fields.status_rejected'),
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function typeOptions(): array
    {
        return [
            'drug' => __('admin-panel.resources.marketplace_proposal.fields.type_drug'),
            'fertilizer' => __('admin-panel.resources.marketplace_proposal.fields.type_fertilizer'),
            'seed' => __('admin-panel.resources.marketplace_proposal.fields.type_seed'),
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function problemTypeOptions(): array
    {
        return [
            'disease' => __('admin-panel.resources.marketplace_proposal.fields.problem_type_disease'),
            'pest' => __('admin-panel.resources.marketplace_proposal.fields.problem_type_pest'),
            'weed' => __('admin-panel.resources.marketplace_proposal.fields.problem_type_weed'),
        ];
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make(__('admin-panel.resources.marketplace_proposal.sections.proposal_information'))
                ->schema([
                    Forms\Components\TextInput::make('proposal_id')
                        ->label(__('admin-panel.resources.marketplace_proposal.fields.proposal_id'))
                        ->numeric()
                        ->disabled()
                        ->dehydrated(false),
                    Forms\Components\TextInput::make('seller_id')
                        ->label(__('admin-panel.resources.marketplace_proposal.fields.seller_id'))
                        ->numeric()
                        ->disabled()
                        ->dehydrated(false),
                    Forms\Components\TextInput::make('seller_name')
                        ->label(__('admin-panel.resources.marketplace_proposal.fields.seller_name'))
                        ->disabled()
                        ->dehydrated(false),
                    Forms\Components\Select::make('status')
                        ->label(__('admin-panel.resources.marketplace_proposal.fields.status'))
                        ->options(self::statusOptions())
                        ->disabled()
                        ->dehydrated(false),
                    Forms\Components\Textarea::make('seller_comment')
                        ->label(__('admin-panel.resources.marketplace_proposal.fields.seller_comment'))
                        ->rows(3)
                        ->disabled()
                        ->dehydrated(false)
                        ->columnSpanFull(),
                ])
                ->columns(4),

            Forms\Components\Section::make(__('admin-panel.resources.marketplace_proposal.sections.product_details'))
                ->schema([
                    Forms\Components\TextInput::make('product_data.name')
                        ->label(__('admin-panel.resources.marketplace_proposal.fields.product_name'))
                        ->maxLength(255),
                    Forms\Components\Select::make('product_data.type')
                        ->label(__('admin-panel.resources.marketplace_proposal.fields.type'))
                        ->options(self::typeOptions()),
                    Forms\Components\TextInput::make('product_data.image_url')
                        ->label(__('admin-panel.resources.marketplace_proposal.fields.product_image_url'))
                        ->url()
                        ->maxLength(2048)
                        ->columnSpanFull(),
                    Forms\Components\Textarea::make('product_data.description')
                        ->label(__('admin-panel.resources.marketplace_proposal.fields.description'))
                        ->rows(4)
                        ->columnSpanFull(),
                ])
                ->columns(2),

            Forms\Components\Section::make(__('admin-panel.resources.marketplace_proposal.sections.proposed_treatments'))
                ->schema([
                    Forms\Components\Repeater::make('treatment_data')
                        ->label('')
                        ->schema([
                            Forms\Components\Select::make('problem_type')
                                ->label(__('admin-panel.resources.marketplace_proposal.fields.problem_type'))
                                ->options(self::problemTypeOptions()),
                            Forms\Components\TextInput::make('problem_id')
                                ->label(__('admin-panel.resources.marketplace_proposal.fields.problem_id')),
                            Forms\Components\TextInput::make('dose_min')
                                ->label(__('admin-panel.resources.marketplace_proposal.fields.dose_min')),
                            Forms\Components\TextInput::make('dose_max')
                                ->label(__('admin-panel.resources.marketplace_proposal.fields.dose_max')),
                            Forms\Components\TextInput::make('dose_unit')
                                ->label(__('admin-panel.resources.marketplace_proposal.fields.dose_unit')),
                            Forms\Components\Textarea::make('instructions')
                                ->label(__('admin-panel.resources.marketplace_proposal.fields.instructions'))
                                ->rows(3)
                                ->columnSpanFull(),
                        ])
                        ->addActionLabel(__('admin-panel.resources.marketplace_proposal.actions.add_treatment'))
                        ->columns(5)
                        ->columnSpanFull(),
                ]),

            Forms\Components\Section::make(__('admin-panel.resources.marketplace_proposal.sections.admin_notes'))
                ->schema([
                    Forms\Components\Textarea::make('admin_notes')
                        ->label(__('admin-panel.resources.marketplace_proposal.fields.admin_notes'))
                        ->rows(4)
                        ->maxLength(1000)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make(__('admin-panel.resources.marketplace_proposal.sections.proposal_information'))
                    ->schema([
                        Infolists\Components\TextEntry::make('proposal_id')
                            ->label(__('admin-panel.resources.marketplace_proposal.fields.proposal_id')),
                        Infolists\Components\TextEntry::make('seller_name')
                            ->label(__('admin-panel.resources.marketplace_proposal.fields.seller_name')),
                        Infolists\Components\TextEntry::make('status')
                            ->label(__('admin-panel.resources.marketplace_proposal.fields.status'))
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => self::statusOptions()[$state] ?? $state)
                            ->color(fn (string $state): string => match ($state) {
                                'pending' => 'warning',
                                'approved' => 'success',
                                'rejected' => 'danger',
                                default => 'gray',
                            }),
                        Infolists\Components\TextEntry::make('created_at')
                            ->label(__('admin-panel.resources.marketplace_proposal.fields.submitted'))
                            ->dateTime(),
                    ])
                    ->columns(4),

                Infolists\Components\Section::make(__('admin-panel.resources.marketplace_proposal.sections.product_details'))
                    ->schema([
                        Infolists\Components\TextEntry::make('product_data.name')
                            ->label(__('admin-panel.resources.marketplace_proposal.fields.product_name')),
                        Infolists\Components\TextEntry::make('product_data.type')
                            ->label(__('admin-panel.resources.marketplace_proposal.fields.type'))
                            ->badge()
                            ->formatStateUsing(fn (?string $state): string => self::typeOptions()[$state] ?? (string) $state)
                            ->color(fn (?string $state): string => match ($state) {
                                'drug' => 'danger',
                                'fertilizer' => 'success',
                                'seed' => 'info',
                                default => 'gray',
                            }),
                        Infolists\Components\TextEntry::make('product_data.description')
                            ->label(__('admin-panel.resources.marketplace_proposal.fields.description'))
                            ->columnSpanFull(),
                        Infolists\Components\ImageEntry::make('product_data.image_url')
                            ->label(__('admin-panel.resources.marketplace_proposal.fields.product_image'))
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make(__('admin-panel.resources.marketplace_proposal.sections.proposed_treatments'))
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('treatment_data')
                            ->label('')
                            ->schema([
                                Infolists\Components\TextEntry::make('problem_type')
                                    ->label(__('admin-panel.resources.marketplace_proposal.fields.problem_type'))
                                    ->badge(),
                                Infolists\Components\TextEntry::make('problem_id')
                                    ->label(__('admin-panel.resources.marketplace_proposal.fields.problem_id')),
                                Infolists\Components\TextEntry::make('dose_min')
                                    ->label(__('admin-panel.resources.marketplace_proposal.fields.dose_min')),
                                Infolists\Components\TextEntry::make('dose_max')
                                    ->label(__('admin-panel.resources.marketplace_proposal.fields.dose_max')),
                                Infolists\Components\TextEntry::make('dose_unit')
                                    ->label(__('admin-panel.resources.marketplace_proposal.fields.dose_unit')),
                                Infolists\Components\TextEntry::make('instructions')
                                    ->label(__('admin-panel.resources.marketplace_proposal.fields.instructions'))
                                    ->columnSpanFull(),
                            ])
                            ->columns(5)
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),

                Infolists\Components\Section::make(__('admin-panel.resources.marketplace_proposal.sections.seller_comment'))
                    ->schema([
                        Infolists\Components\TextEntry::make('seller_comment')
                            ->label('')
                            ->columnSpanFull(),
                    ])
                    ->visible(fn (MarketplaceProposal $record) => ! empty($record->seller_comment)),

                Infolists\Components\Section::make(__('admin-panel.resources.marketplace_proposal.sections.admin_notes'))
                    ->schema([
                        Infolists\Components\TextEntry::make('admin_notes')
                            ->label('')
                            ->columnSpanFull(),
                    ])
                    ->visible(fn (MarketplaceProposal $record) => ! empty($record->admin_notes)),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('proposal_id')
                    ->label(__('admin-panel.resources.marketplace_proposal.fields.id'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('product_data.name')
                    ->label(__('admin-panel.resources.marketplace_proposal.fields.product_name'))
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereRaw("product_data->>'name' ILIKE ?", ["%{$search}%"]);
                    }),
                Tables\Columns\TextColumn::make('product_data.type')
                    ->label(__('admin-panel.resources.marketplace_proposal.fields.type'))
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => self::typeOptions()[$state] ?? (string) $state)
                    ->color(fn (?string $state): string => match ($state) {
                        'drug' => 'danger',
                        'fertilizer' => 'success',
                        'seed' => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('seller_name')
                    ->label(__('admin-panel.resources.marketplace_proposal.fields.seller_name'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('admin-panel.resources.marketplace_proposal.fields.status'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => self::statusOptions()[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('treatments_count')
                    ->label(__('admin-panel.resources.marketplace_proposal.fields.treatments'))
                    ->getStateUsing(fn (MarketplaceProposal $record) => count($record->treatment_data ?? []))
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('admin-panel.resources.marketplace_proposal.fields.submitted'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('admin-panel.resources.marketplace_proposal.fields.status'))
                    ->options(self::statusOptions()),
                Tables\Filters\SelectFilter::make('type')
                    ->label(__('admin-panel.resources.marketplace_proposal.fields.product_type'))
                    ->options(self::typeOptions())
                    ->query(function (Builder $query, array $data): Builder {
                        return $data['value']
                            ? $query->whereRaw("product_data->>'type' = ?", [$data['value']])
                            : $query;
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('approve')
                    ->label(__('admin-panel.resources.marketplace_proposal.actions.approve'))
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading(__('admin-panel.resources.marketplace_proposal.actions.approve_modal_heading'))
                    ->modalDescription(__('admin-panel.resources.marketplace_proposal.actions.approve_modal_description'))
                    ->form([
                        Forms\Components\Textarea::make('admin_notes')
                            ->label(__('admin-panel.resources.marketplace_proposal.fields.admin_notes_optional'))
                            ->maxLength(1000),
                    ])
                    ->action(function (MarketplaceProposal $record, array $data) {
                        app(ProposalApprovalService::class)->approve($record, $data['admin_notes'] ?? null);
                    })
                    ->visible(fn (MarketplaceProposal $record) => $record->status === 'pending'),
                Tables\Actions\Action::make('reject')
                    ->label(__('admin-panel.resources.marketplace_proposal.actions.reject'))
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading(__('admin-panel.resources.marketplace_proposal.actions.reject_modal_heading'))
                    ->form([
                        Forms\Components\Textarea::make('rejection_reason')
                            ->label(__('admin-panel.resources.marketplace_proposal.fields.rejection_reason'))
                            ->required()
                            ->maxLength(1000),
                    ])
                    ->action(function (MarketplaceProposal $record, array $data) {
                        app(ProposalApprovalService::class)->reject($record, $data['rejection_reason']);
                    })
                    ->visible(fn (MarketplaceProposal $record) => $record->status === 'pending'),
            ])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMarketplaceProposals::route('/'),
            'view' => Pages\ViewMarketplaceProposal::route('/{record}'),
            'edit' => Pages\EditMarketplaceProposal::route('/{record}/edit'),
        ];
    }
}
