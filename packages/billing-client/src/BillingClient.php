<?php

declare(strict_types=1);

namespace Makhweb\BillingClient;

use Illuminate\Support\Facades\Http;
use Makhweb\BillingClient\DTO\ClientResponseDTO;
use Makhweb\BillingClient\DTO\RpcRequest\AvailablePaymentSystemsRequestDTO;
use Makhweb\BillingClient\DTO\RpcRequest\ConfirmOtpRequestDTO;
use Makhweb\BillingClient\DTO\RpcRequest\ConfirmPaymentRequestDTO;
use Makhweb\BillingClient\DTO\RpcRequest\CreateBalanceRequestDTO;
use Makhweb\BillingClient\DTO\RpcRequest\CreateBillingOrganizationRequestDTO;
use Makhweb\BillingClient\DTO\RpcRequest\CreateBillingReflectionRequestDTO;
use Makhweb\BillingClient\DTO\RpcRequest\CreateDebtUserRequestDTO;
use Makhweb\BillingClient\DTO\RpcRequest\CreatePaymentSystemRequestDTO;
use Makhweb\BillingClient\DTO\RpcRequest\DeleteExternalEntityRequestDTO;
use Makhweb\BillingClient\DTO\RpcRequest\ExternalEntitiesRequestDTO;
use Makhweb\BillingClient\DTO\RpcRequest\GenerateLinkRequestDTO;
use Makhweb\BillingClient\DTO\RpcRequest\GetUserBalancesRequestDTO;
use Makhweb\BillingClient\DTO\RpcRequest\PaymentRequestDTO;
use Makhweb\BillingClient\DTO\RpcRequest\RevertTransactionToBalanceRequestDTO;
use Makhweb\BillingClient\DTO\RpcRequest\SendOtpRequestDTO;
use Makhweb\BillingClient\DTO\RpcRequest\SetDefaultExternalEntityRequestDTO;
use Makhweb\BillingClient\DTO\RpcResponse\AvailablePaymentSystemsResponse;
use Makhweb\BillingClient\DTO\RpcResponse\BalancesResponse;
use Makhweb\BillingClient\DTO\RpcResponse\ExternalEntitiesResponse;
use Makhweb\BillingClient\DTO\RpcResponse\GeneratePaymentLinkResponse;
use Makhweb\BillingClient\DTO\RpcResponse\OtpResponse;
use Makhweb\BillingClient\DTO\RpcResponse\PaymentResponse;
use Makhweb\BillingClient\DTO\RpcResponse\PaymentSystemsDriversResponse;
use Makhweb\BillingClient\DTO\RpcResponse\Response;
use Makhweb\BillingClient\Events\BillingRpcErrorOccurredEvent;
use Makhweb\BillingClient\Exceptions\BillingClientException;
use Sajya\Client\Client;

class BillingClient
{
    private Client $client;

    protected ?string $billingReflectionAliasDefault = null;

    protected string $userIdDefault;

    protected function request(string $method, array $params)
    {
        $headers = [
            'Accept-Language' => app()->getLocale(),
            'x-application-alias' => config('billing-client.application_alias'),
        ];

        $http = Http::baseUrl(config('billing-client.rpc_endpoint'))
            ->withHeaders($headers);

        if ($token = request()->bearerToken()) {
            $http = $http->withToken($token);
        }

        $client = new Client($http);

        $rawResponse = $client->execute($method, $params);
        $clientResponse = new ClientResponseDTO($rawResponse);

        if ($clientResponse->isError()) {
            $event = new BillingRpcErrorOccurredEvent($clientResponse, $method, $params);

            event($event);
        }

        return $clientResponse;
    }

    public function setDefaultBillingReflectionAlias(string $billingReflectionAlias): self
    {
        $this->billingReflectionAliasDefault = $billingReflectionAlias;

        return $this;
    }

    public function setDefaultUserId(string $userId): self
    {
        $this->userIdDefault = $userId;

        return $this;
    }

    /**
     * @return GeneratePaymentLinkResponse
     *
     * @throws BillingClientException
     */
    public function generatePaymentLink(GenerateLinkRequestDTO $DTO)
    {
        $response = $this->request('paymentSystem.generateUrl', [
            'entity_id' => $DTO->entityId,
            'amount' => $DTO->amount,
            'payment_system_alias' => $DTO->paymentSystemAlias,
            'billing_reflection_alias' => $DTO->billingReflectionAlias ?? $this->billingReflectionAliasDefault,
        ]);

        return new GeneratePaymentLinkResponse($response);
    }

    public function availablePaymentSystems(AvailablePaymentSystemsRequestDTO $DTO)
    {
        $response = $this->request('paymentSystem.availablePaymentSystems', [
            'billing_reflection_alias' => $DTO->billingReflectionAlias ?? $this->billingReflectionAliasDefault,
        ]);

        return new AvailablePaymentSystemsResponse($response);
    }

    /**
     * @return Response
     *
     * @throws BillingClientException
     */
    public function createOrActivateDebtUser(CreateDebtUserRequestDTO $DTO)
    {
        $response = $this->request('userDebts.create', [
            'user_id' => $DTO->userId,
            'passport' => $DTO->passport,
            'pinfl' => $DTO->pinfl,
            'first_name' => $DTO->firstName,
            'last_name' => $DTO->lastName,
            'middle_name' => $DTO->middleName,
            'phone_numbers' => $DTO->phoneNumbers,
            'entity_id' => $DTO->entityId,
            'bank_card_id' => $DTO->bankCardId,
            'billing_reflection_alias' => $DTO->billingReflectionAlias ?? $this->billingReflectionAliasDefault,
            'initial_debt_sum' => $DTO->initialDebtSum,
            'payment_start_at' => $DTO->paymentStartAt,
        ]);

        return new Response($response);
    }

    public function sendOtp(SendOtpRequestDTO $sendOtpRequestDTO)
    {
        $response = $this->request('apiPaymentProvider.sendOtp', [
            'billing_reflection_alias' => $sendOtpRequestDTO->billingReflectionAlias ?? $this->billingReflectionAliasDefault,
            'payment_system_alias' => $sendOtpRequestDTO->paymentSystemAlias,
            'user_id' => $sendOtpRequestDTO->userId ?? $this->userIdDefault,
            'pan' => $sendOtpRequestDTO->pan,
            'expiry' => $sendOtpRequestDTO->expiry,
            'phone' => $sendOtpRequestDTO->phone,
        ]);

        return new OtpResponse($response);
    }

    public function confirmOtp(ConfirmOtpRequestDTO $confirmOtpRequestDTO)
    {
        $response = $this->request('apiPaymentProvider.confirmOtp', [
            'billing_reflection_alias' => $confirmOtpRequestDTO->billingReflectionAlias ?? $this->billingReflectionAliasDefault,
            'payment_system_alias' => $confirmOtpRequestDTO->paymentSystemAlias,
            'user_id' => $confirmOtpRequestDTO->userId ?? $this->userIdDefault,
            'pan' => $confirmOtpRequestDTO->pan,
            'expiry' => $confirmOtpRequestDTO->expiry,
            'phone' => $confirmOtpRequestDTO->phone,
            'code' => $confirmOtpRequestDTO->code,
        ]);

        return new OtpResponse($response);
    }

    public function getExternalEntities(ExternalEntitiesRequestDTO $externalEntitiesRequestDTO)
    {
        $response = $this->request('apiPaymentProvider.getUserExternalEntities', [
            'payment_system_alias' => $externalEntitiesRequestDTO->paymentSystemAlias,
            'user_id' => $externalEntitiesRequestDTO->userId ?? $this->userIdDefault,
            'fetch_balances' => $externalEntitiesRequestDTO->fetchBalances,
        ]);

        return new ExternalEntitiesResponse($response);
    }

    public function setDefaultExternalEntity(SetDefaultExternalEntityRequestDTO $DTO)
    {
        $response = $this->request('apiPaymentProvider.setDefaultExternalEntity', [
            'payment_system_alias' => $DTO->paymentSystemAlias,
            'user_id' => $DTO->userId ?? $this->userIdDefault,
            'external_entity_id' => $DTO->externalEntityId,
        ]);

        return new Response($response);
    }

    public function deleteExternalEntity(DeleteExternalEntityRequestDTO $externalEntitiesRequestDTO)
    {
        $response = $this->request('apiPaymentProvider.deleteExternalEntity', [
            'payment_system_alias' => $externalEntitiesRequestDTO->paymentSystemAlias,
            'user_id' => $externalEntitiesRequestDTO->userId ?? $this->userIdDefault,
            'external_entity_id' => $externalEntitiesRequestDTO->externalEntityId,
        ]);

        return new Response($response);
    }

    public function payment(PaymentRequestDTO $paymentRequestDTO)
    {
        $response = $this->request('apiPaymentProvider.payment', [
            'billing_reflection_alias' => $paymentRequestDTO->billingReflectionAlias ?? $this->billingReflectionAliasDefault,
            'payment_system_alias' => $paymentRequestDTO->paymentSystemAlias,
            'user_id' => $paymentRequestDTO->userId ?? $this->userIdDefault,
            'payment_entity_id' => $paymentRequestDTO->paymentEntityId,
            'entity_id' => $paymentRequestDTO->entityId,
            'amount' => $paymentRequestDTO->amount,
            'process_without_confirmation' => $paymentRequestDTO->processWithoutConfirmation,
        ]);

        return new PaymentResponse($response);
    }

    public function confirmPayment(ConfirmPaymentRequestDTO $confirmPaymentRequestDTO)
    {
        $response = $this->request('apiPaymentProvider.confirmPayment', [
            'billing_reflection_alias' => $confirmPaymentRequestDTO->billingReflectionAlias ?? $this->billingReflectionAliasDefault,
            'payment_system_alias' => $confirmPaymentRequestDTO->paymentSystemAlias,
            'user_id' => $confirmPaymentRequestDTO->userId ?? $this->userIdDefault,
            'transaction_id' => $confirmPaymentRequestDTO->transactionId,
            'otp' => $confirmPaymentRequestDTO->otp,
        ]);

        return new Response($response);
    }

    public function createBalance(CreateBalanceRequestDTO $createBalanceRequestDTO)
    {
        $response = $this->request('balances.create', [
            'payment_system_aliases' => $createBalanceRequestDTO->paymentSystemAliases,
            'user_id' => $createBalanceRequestDTO->userId ?? $this->userIdDefault,
        ]);

        return new ExternalEntitiesResponse($response);
    }

    public function revertTransactionToBalance(RevertTransactionToBalanceRequestDTO $revertTransactionToBalanceRequestDTO)
    {
        $response = $this->request('apiPaymentProvider.revertTransactionToBalance', [
            'system_transaction_id' => $revertTransactionToBalanceRequestDTO->systemTransactionId,
            'transaction_system_alias' => $revertTransactionToBalanceRequestDTO->transactionSystemAlias,
            'balance_system_alias' => $revertTransactionToBalanceRequestDTO->balanceSystemAlias,
        ]);

        return new Response($response);
    }

    public function createBillingOrganization(CreateBillingOrganizationRequestDTO $DTO)
    {
        $response = $this->request('billingOrganization.create', [
            'name' => $DTO->name,
            'alias' => $DTO->alias,
        ]);

        return new Response($response);
    }

    public function createBillingReflection(CreateBillingReflectionRequestDTO $DTO)
    {
        $response = $this->request('billingReflection.create', [
            'name' => $DTO->name,
            'alias' => $DTO->alias,
            'payment_type' => $DTO->paymentType,
        ]);

        return new Response($response);
    }

    public function createPaymentSystem(CreatePaymentSystemRequestDTO $DTO)
    {
        $response = $this->request('paymentSystemInternal.create', [
            'name' => $DTO->name,
            'driver' => $DTO->driver,
            'alias' => $DTO->alias,
            'min_amount' => $DTO->minAmount,
            'max_amount' => $DTO->maxAmount,
            'billing_organization_alias' => $DTO->billingOrganizationAlias,
            'billing_reflection_alias' => $DTO->billingReflectionAlias,
            'credentials' => $DTO->credentials,
        ]);

        return new Response($response);
    }

    public function getBillingReflectionPaymentSystems(string $billingReflectionAlias)
    {
        $response = $this->request('paymentSystemInternal.all', [
            'billing_reflection_alias' => $billingReflectionAlias,
        ]);

        return new AvailablePaymentSystemsResponse($response);
    }

    public function getPaymentSystemDrivers()
    {
        $response = $this->request('paymentSystemInternal.drivers', []);

        return new PaymentSystemsDriversResponse($response);
    }

    /**
     * Get all balances for a user.
     */
    public function getUserBalances(GetUserBalancesRequestDTO $dto): BalancesResponse
    {
        $response = $this->request('balances.getUserBalances', [
            'owner_id' => $dto->ownerId,
            'owner_type' => $dto->ownerType,
            'balance_type' => $dto->balanceType,
        ]);

        return new BalancesResponse($response);
    }

    /**
     * Get a specific balance by type for a user.
     */
    public function getUserBalanceByType(GetUserBalancesRequestDTO $dto): BalancesResponse
    {
        $response = $this->request('balances.getUserBalanceByType', [
            'owner_id' => $dto->ownerId,
            'owner_type' => $dto->ownerType,
            'balance_type' => $dto->balanceType,
        ]);

        return new BalancesResponse($response);
    }
}
