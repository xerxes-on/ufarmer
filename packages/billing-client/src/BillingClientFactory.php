<?php

declare(strict_types=1);

namespace Makhweb\BillingClient;

class BillingClientFactory
{
    public static function buildInitial()
    {
        $client = new BillingClient;

        if ($userId = auth()->id()) {
            $client->setDefaultUserId($userId);
        }

        return $client;
    }

    public static function fromOrderReflection(): BillingClient
    {
        return self::buildInitial()
            ->setDefaultBillingReflectionAlias(config('billing-client.order_reflection_alias'));
    }

    public static function fromBalanceReflection(): BillingClient
    {
        return self::buildInitial()
            ->setDefaultBillingReflectionAlias(config('billing-client.balance_reflection_alias'));
    }

    public static function fromAutoPaymentReflection(): BillingClient
    {
        return self::buildInitial()
            ->setDefaultBillingReflectionAlias(config('billing-client.autopayment_reflection_alias'));
    }

    public static function make(): BillingClient
    {
        return new BillingClient;
    }
}
