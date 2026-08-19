<?php

declare(strict_types=1);

namespace Modules\Core\Http\Requests\UserDetail;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserDetailUpdateRequest extends FormRequest
{
    public const FIELD_NAME = UserDetailStoreRequest::FIELD_NAME;

    public const FIELD_PHONE = 'phone';

    public const FIELD_REGION_ID = UserDetailStoreRequest::FIELD_REGION_ID;

    public const FIELD_CITY_ID = UserDetailStoreRequest::FIELD_CITY_ID;

    public const FIELD_ADDRESS = 'address';

    public const FIELD_LATITUDE = 'latitude';

    public const FIELD_LONGITUDE = 'longitude';

    public const FIELD_IMAGE = 'image';

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            self::FIELD_NAME => ['sometimes', 'required', 'string', 'max:255'],
            self::FIELD_PHONE => ['sometimes', 'required', 'string', 'max:20'],
            self::FIELD_REGION_ID => ['sometimes', 'required', 'exists:regions,id'],
            self::FIELD_CITY_ID => [
                'sometimes',
                'required',
                $this->filled(self::FIELD_REGION_ID)
                    ? Rule::exists('cities', 'id')->where('region_id', $this->input(self::FIELD_REGION_ID))
                    : Rule::exists('cities', 'id'),
            ],
            self::FIELD_ADDRESS => ['nullable', 'string', 'max:255'],
            self::FIELD_LATITUDE => ['nullable', 'numeric', 'between:-90,90'],
            self::FIELD_LONGITUDE => ['nullable', 'numeric', 'between:-180,180'],
            self::FIELD_IMAGE => ['nullable', 'image', 'max:5120'],
        ];
    }

    public function userAttributes(): array
    {
        $attributes = [];

        if ($this->has(self::FIELD_NAME)) {
            $attributes[self::FIELD_NAME] = $this->string(self::FIELD_NAME)->toString();
        }

        if ($this->has(self::FIELD_PHONE)) {
            $attributes[self::FIELD_PHONE] = $this->string(self::FIELD_PHONE)->toString();
        }

        return $attributes;
    }

    public function detailAttributes(): array
    {
        $fields = [
            self::FIELD_REGION_ID,
            self::FIELD_CITY_ID,
            self::FIELD_ADDRESS,
            self::FIELD_LATITUDE,
            self::FIELD_LONGITUDE,
        ];

        $attributes = [];

        foreach ($fields as $field) {
            if ($this->has($field)) {
                $value = $this->input($field);

                if (in_array($field, [self::FIELD_REGION_ID, self::FIELD_CITY_ID], true) && $value !== null) {
                    $value = (int) $value;
                }

                if (in_array($field, [self::FIELD_LATITUDE, self::FIELD_LONGITUDE], true) && $value !== null) {
                    $value = (float) $value;
                }

                $attributes[$field] = $value;
            }
        }

        return $attributes;
    }

    public function image()
    {
        return $this->file(self::FIELD_IMAGE);
    }
}
