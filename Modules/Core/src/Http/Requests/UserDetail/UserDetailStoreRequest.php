<?php

declare(strict_types=1);

namespace Modules\Core\Http\Requests\UserDetail;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserDetailStoreRequest extends FormRequest
{
    public const FIELD_NAME = 'name';

    public const FIELD_REGION_ID = 'region_id';

    public const FIELD_CITY_ID = 'city_id';

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            self::FIELD_NAME => ['required', 'string', 'max:255'],
            self::FIELD_REGION_ID => ['required', 'exists:regions,id'],
            self::FIELD_CITY_ID => [
                'required',
                Rule::exists('cities', 'id')->where(fn (\Illuminate\Database\Query\Builder $query) => $query->where('region_id', $this->input(self::FIELD_REGION_ID))),
            ],
        ];
    }

    public function name(): string
    {
        return $this->string(self::FIELD_NAME)->toString();
    }

    public function regionId(): int
    {
        return (int) $this->input(self::FIELD_REGION_ID);
    }

    public function cityId(): int
    {
        return (int) $this->input(self::FIELD_CITY_ID);
    }
}
