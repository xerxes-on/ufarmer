<?php

declare(strict_types=1);

namespace Modules\Agronom\Enums;

enum ServiceRequestStatus: string
{
    case PENDING = 'pending';
    case FARMER_REVIEW = 'farmer_review';
    case CONFIRMED = 'confirmed';
    case REJECTED = 'rejected';
    case PAYMENT_PENDING = 'payment_pending';
    case PAID = 'paid';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case REOPENED = 'reopened';
    case DISPUTED = 'disputed';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return __('admin-panel.resources.service_request.statuses.'.$this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::FARMER_REVIEW => 'info',
            self::CONFIRMED => 'info',
            self::REJECTED => 'danger',
            self::PAYMENT_PENDING => 'info',
            self::PAID => 'success',
            self::IN_PROGRESS => 'primary',
            self::COMPLETED => 'success',
            self::REOPENED => 'warning',
            self::DISPUTED => 'danger',
            self::CANCELLED => 'gray',
        };
    }
}
