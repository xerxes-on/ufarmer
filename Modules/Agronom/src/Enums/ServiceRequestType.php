<?php

declare(strict_types=1);

namespace Modules\Agronom\Enums;

enum ServiceRequestType: string
{
    case Chat = 'chat';
    case InPerson = 'in_person';
    case Monitoring = 'monitoring';

    public function label(): string
    {
        return __('admin-panel.resources.service_request.types.'.$this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::Chat => 'info',
            self::InPerson => 'success',
            self::Monitoring => 'primary',
        };
    }
}
