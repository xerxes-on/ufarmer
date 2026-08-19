<?php

declare(strict_types=1);

namespace Makhweb\BillingClient\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller;
use Makhweb\BillingClient\Contracts\PaymentWebhookContract;
use Makhweb\BillingClient\DTO\TransactionDTO;
use Makhweb\BillingClient\DTO\WebhookRequest\AuthorizeRequestDTO;
use Makhweb\BillingClient\DTO\WebhookRequest\CheckTransactionRevertAllowedTransactionDTO;
use Makhweb\BillingClient\DTO\WebhookRequest\CurrencyRateRequestDTO;
use Makhweb\BillingClient\Http\Requests\AuthorizeRequest;
use Makhweb\BillingClient\Http\Requests\CurrencyRateRequest;
use Makhweb\BillingClient\Http\Requests\TransactionHandleRequest;
use Makhweb\BillingClient\Http\Requests\TransactionRevertAllowedRequest;
use Spatie\Crypto\Rsa\PublicKey;

class WebhookController extends Controller
{
    use AuthorizesRequests, ValidatesRequests;

    private PaymentWebhookContract $contract;

    public function __construct(PaymentWebhookContract $paymentWebhookContract)
    {
        $this->contract = $paymentWebhookContract;

        $this->middleware(function ($request, $next) {
            $publicKeyPath = base_path(config('billing-client.public_key_path'));

            $publicKey = PublicKey::fromFile($publicKeyPath);

            $body = json_encode(request()->all());

            if (! $publicKey->verify($body, $request->header('X-Signature', ''))) {
                return abort(403);
            }

            return $next($request);
        });
    }

    public function transactionHandle(TransactionHandleRequest $request)
    {
        $dto = TransactionDTO::fromRequest($request);

        $transactionId = $this->contract->handleTransaction($dto);

        return response()->json([
            'status' => 'ok',
            'data' => [
                'transaction_id' => $transactionId,
            ],
        ]);
    }

    public function authorize(AuthorizeRequest $request)
    {
        $dto = AuthorizeRequestDTO::fromRequest($request);

        $response = $this->contract->authorize($dto);

        return response()->json([
            'allowed' => $response->allowed,
            'currency_rate' => optional($response->currencyRateDTO)->toArray(),
            'data' => $response->data,
            'error' => [
                'code' => $response->errorCode,
                'message' => $response->errorMessage,
            ],
        ]);
    }

    public function transactionRevertAllowed(TransactionRevertAllowedRequest $request)
    {
        $dto = CheckTransactionRevertAllowedTransactionDTO::fromRequest($request);

        $result = $this->contract->checkTransactionRevertAllowed($dto);

        return response()->json(['result' => $result->toArray()]);
    }

    public function currencyRate(CurrencyRateRequest $request)
    {
        $dto = CurrencyRateRequestDTO::fromRequest($request);

        $result = $this->contract->currencyRate($dto);

        return response()->json(['result' => $result->toArray()]);
    }
}
