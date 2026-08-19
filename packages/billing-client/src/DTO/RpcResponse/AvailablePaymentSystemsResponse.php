<?php

declare(strict_types=1);

namespace Makhweb\BillingClient\DTO\RpcResponse;

use Makhweb\BillingClient\DTO\PaymentSystemDTO;

class AvailablePaymentSystemsResponse extends Response
{
    /**
     * @return PaymentSystemDTO[]
     */
    public function paymentSystems()
    {
        $items = data_get($this->response->getResult(), 'data.payment_systems', []);

        return array_map(function ($item) {
            return PaymentSystemDTO::fromArray([
                'name' => $item['name'],
                'alias' => $item['alias'],
                'logo' => $item['logo'],
                'providerName' => $item['provider_name'],
                'providerType' => $item['provider_type'],
            ]);
        }, $items);
    }
}
