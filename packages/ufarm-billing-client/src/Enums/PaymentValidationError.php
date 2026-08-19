<?php

declare(strict_types=1);

namespace Xerxes\BillingClient\Enums;

enum PaymentValidationError: string
{
    case EntityNotFound = 'ENTITY_NOT_FOUND';
    case InvalidStatus = 'INVALID_STATUS';
    case AmountMismatch = 'AMOUNT_MISMATCH';
    case UserMismatch = 'USER_MISMATCH';
    case ServiceInactive = 'SERVICE_INACTIVE';
    case AlreadyPaid = 'ALREADY_PAID';
    case UnknownServiceType = 'UNKNOWN_SERVICE_TYPE';
    case InvalidEntityFormat = 'INVALID_ENTITY_FORMAT';
    case CurrencyMismatch = 'CURRENCY_MISMATCH';
    case ValidationFailed = 'VALIDATION_FAILED';

    public function message(): string
    {
        return match ($this) {
            self::EntityNotFound => 'The requested entity was not found.',
            self::InvalidStatus => 'Entity is not in a valid status for payment.',
            self::AmountMismatch => 'Payment amount does not match entity cost.',
            self::UserMismatch => 'Payment user does not match entity owner.',
            self::ServiceInactive => 'The service is currently inactive.',
            self::AlreadyPaid => 'This entity has already been paid for.',
            self::UnknownServiceType => 'Unknown payment service type.',
            self::InvalidEntityFormat => 'Invalid entity ID format.',
            self::CurrencyMismatch => 'Currency mismatch between payment and entity.',
            self::ValidationFailed => 'Payment validation failed.',
        };
    }
}
