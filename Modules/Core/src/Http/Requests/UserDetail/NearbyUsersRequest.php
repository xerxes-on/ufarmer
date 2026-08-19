<?php

declare(strict_types=1);

namespace Modules\Core\Http\Requests\UserDetail;

use Illuminate\Foundation\Http\FormRequest;

class NearbyUsersRequest extends FormRequest
{
    public const FIELD_LATITUDE = 'latitude';

    public const FIELD_LONGITUDE = 'longitude';

    public const FIELD_RADIUS = 'radius';

    public const FIELD_PER_PAGE = 'per_page';

    private const DEFAULT_PER_PAGE = 15;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            self::FIELD_LATITUDE => ['required', 'numeric', 'between:-90,90'],
            self::FIELD_LONGITUDE => ['required', 'numeric', 'between:-180,180'],
            self::FIELD_RADIUS => ['required', 'numeric', 'min:0.1', 'max:500'],
            self::FIELD_PER_PAGE => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function latitude(): float
    {
        return (float) $this->input(self::FIELD_LATITUDE);
    }

    public function longitude(): float
    {
        return (float) $this->input(self::FIELD_LONGITUDE);
    }

    public function radius(): float
    {
        return (float) $this->input(self::FIELD_RADIUS);
    }

    public function perPage(): int
    {
        return (int) $this->input(self::FIELD_PER_PAGE, self::DEFAULT_PER_PAGE);
    }
}
