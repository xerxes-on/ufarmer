<?php

declare(strict_types=1);

namespace App\Filament\Resources\EmployeeResource\Pages;

use App\Filament\Resources\EmployeeResource;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role;

class CreateEmployee extends CreateRecord
{
    protected static string $resource = EmployeeResource::class;

    /**
     * Lets an admin either search for an existing local `User` (any farmer-app
     * account can be promoted to an employee by granting a role) or create a
     * brand-new one inline via the "Create" option — mirrors the pattern
     * already shipped in ufarm-marketplace's Employees resource.
     */
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('user_id')
                    ->label('User')
                    ->searchable()
                    ->helperText('Search by phone or name. Not found? Type the phone number and use "Create" below.')
                    ->getSearchResultsUsing(fn (string $search): array => User::query()
                        ->select(['id', 'name', 'phone'])
                        ->where(fn ($query) => $query->where('phone', 'like', "%{$search}%")
                            ->orWhere('name', 'like', "%{$search}%"))
                        ->limit(20)
                        ->get()
                        ->mapWithKeys(fn (User $user): array => [$user->id => self::userLabel($user)])
                        ->all())
                    ->getOptionLabelUsing(fn ($value): ?string => ($user = User::find($value)) ? self::userLabel($user) : null)
                    ->createOptionForm([
                        TextInput::make('phone')
                            ->required()
                            ->unique('users', 'phone')
                            ->maxLength(20),
                        TextInput::make('password')
                            ->label('Password')
                            ->password()
                            ->revealable()
                            ->minLength(8)
                            ->helperText("Optional — only used if this phone doesn't already have an SSO account. Leave blank to require the employee to log in via OTP on first use."),
                    ])
                    ->createOptionUsing(fn (array $data): int => self::createUserWithAuthId($data['phone'], $data['password'] ?? null))
                    ->required(),

                Select::make('roles')
                    ->label('Roles')
                    ->options(fn () => Role::query()->pluck('name', 'name'))
                    ->multiple()
                    ->required(),
            ]);
    }

    protected function handleRecordCreation(array $data): Model
    {
        /** @var User $user */
        $user = User::findOrFail($data['user_id']);
        $user->syncRoles($data['roles']);

        return $user;
    }

    /**
     * Creates the local `User` row first (without `auth_id`), then resolves
     * the AuthBridge `auth_id` for the given phone via SSO — `entity_id` can
     * only be known once the row exists. Extracted from the Select's
     * `createOptionUsing` callback so it's reflectable/testable the same way
     * as `EditEmployee::handleRecordUpdate()`, rather than only reachable
     * through a Livewire form interaction.
     */
    private static function createUserWithAuthId(string $phone, ?string $password = null): int
    {
        $user = User::create(['phone' => $phone]);

        $authId = EmployeeResource::resolveAuthId($phone, filled($password) ? $password : null, entityId: $user->id);

        if ($authId !== null) {
            $user->forceFill(['auth_id' => $authId])->save();
        }

        return $user->getKey();
    }

    private static function userLabel(User $user): string
    {
        $name = trim((string) $user->name);

        return $name !== '' ? "{$name} ({$user->phone})" : (string) $user->phone;
    }
}
