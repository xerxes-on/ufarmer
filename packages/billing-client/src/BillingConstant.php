<?php

declare(strict_types=1);

namespace Makhweb\BillingClient;

class BillingConstant
{
    const ERROR_INVALID_ENTITY = 'invalid_entity';

    const ERROR_INVALID_COST = 'invalid_cost';

    const ERROR_ORDER_ALREADY_PAID = 'order_already_paid';

    const TYPE_USER_ORDER = 'user_order';

    const TYPE_USER_BALANCE = 'user_balance';

    const TYPE_USER_AUTO_PAYMENT = 'user_auto_payment';

    const PROVIDER_TYPE_API = 'api';

    const PROVIDER_TYPE_WEBHOOK = 'webhook';

    const PROVIDER_TYPE_AUTO_PAYMENT = 'auto_payment';

    const TRANSACTION_STATE_COMMITTED = 'commited';

    const TRANSACTION_STATE_REVERTED_TO_BALANCE = 'reverted_to_balance';

    const TRANSACTION_STATE_CANCELED_AFTER_COMMIT = 'canceled_after_commit';
}
