<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\NavigationGroup;
use App\Filament\Resources\EmployeeResource\Pages;
use App\Models\User;
use App\Services\Auth\PanelAuthUserProvisioner;
use App\Traits\HasTranslatedFilamentLabels;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EmployeeResource extends Resource
{
    use HasTranslatedFilamentLabels;

    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Employees';

    protected static ?string $modelLabel = 'Employee';

    protected static ?string $pluralModelLabel = 'Employees';

    protected static ?string $navigationGroup = NavigationGroup::Administration->value;

    protected static ?int $navigationSort = 20;

    protected static function translationKey(): string
    {
        return 'employee';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('admin-panel.resources.employee.sections.user'))
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label(__('admin-panel.resources.employee.fields.name'))
                            ->maxLength(255),
                        Forms\Components\TextInput::make('phone')
                            ->label(__('admin-panel.resources.employee.fields.phone'))
                            ->unique(ignoreRecord: true)
                            ->disabled(fn (string $operation): bool => $operation === 'edit')
                            ->dehydrated()
                            ->maxLength(20),
                        Forms\Components\TextInput::make('auth_id')
                            ->label(__('admin-panel.resources.employee.fields.auth_id'))
                            ->numeric()
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText('Resolved automatically from the phone number via SSO on save — leave blank to let it link up automatically.'),
                        Forms\Components\TextInput::make('password')
                            ->label(__('admin-panel.resources.employee.fields.password'))
                            ->password()
                            ->revealable()
                            ->minLength(8)
                            ->visible(fn (string $operation): bool => $operation === 'create')
                            ->dehydrated(fn (string $operation): bool => $operation === 'create')
                            ->helperText("Optional — only used if this phone doesn't already have an SSO account. Leave blank to require the employee to log in via OTP on first use."),
                    ])
                    ->columns(2),

                Forms\Components\Section::make(__('admin-panel.resources.employee.sections.roles'))
                    ->schema([
                        Forms\Components\Select::make('roles')
                            ->label('')
                            ->relationship('roles', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('roles'))
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('admin-panel.resources.employee.fields.name'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label(__('admin-panel.resources.employee.fields.phone'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('auth_id')
                    ->label(__('admin-panel.resources.employee.fields.auth_id'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('roles.name')
                    ->label(__('admin-panel.resources.employee.fields.roles'))
                    ->badge(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('roles')
                    ->relationship('roles', 'name')
                    ->label(__('admin-panel.resources.employee.fields.role')),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmployees::route('/'),
            'create' => Pages\CreateEmployee::route('/create'),
            'edit' => Pages\EditEmployee::route('/{record}/edit'),
        ];
    }

    /**
     * The `users` table is shared with the farmer-facing app — an employee
     * is any user who has been granted a panel role. Scoping by "has any
     * role" rather than a hardcoded role list means new roles (there's
     * already `content_manager` alongside `admin`/`super_admin`) are
     * automatically included without a code change.
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('roles');
    }

    /**
     * Single entry point for both Create and Edit pages to resolve the
     * AuthBridge `auth_id` for an employee's phone number. The application
     * alias is config-driven (`services.authbridge.employee_application_alias`)
     * rather than hardcoded, since the correct alias is gated by the
     * receiving OAuth client's required_applications and can differ per
     * caller/panel.
     */
    public static function resolveAuthId(string $phone, ?string $password = null, ?int $entityId = null): ?int
    {
        if (blank($phone)) {
            return null;
        }

        $applicationAlias = (string) config('services.authbridge.employee_application_alias', 'admin_panel');

        return app(PanelAuthUserProvisioner::class)->resolveAuthId($phone, $applicationAlias, $password, $entityId);
    }
}
