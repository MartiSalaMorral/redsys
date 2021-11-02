<?php


namespace Revosystems\Redsys\Services;

use Revosystems\Redsys\Lib\Utils\Price;
use Revosystems\Redsys\Models\ChargeResult;
use Revosystems\Redsys\Lib\Constants\RESTConstants;
use Revosystems\Redsys\Lib\Model\Message\RESTRefundRequestOperationMessage;
use Revosystems\Redsys\Lib\Service\Impl\RESTTrataRequestService;
use Illuminate\Support\Facades\Log;

class RedsysRequestRefund extends RedsysRequest
{
    public function handle(string $paymentReference, Price $price) : ChargeResult
    {
        $requestOperation = (new RESTRefundRequestOperationMessage)
            ->generate($this->config, $paymentReference, null, $price);
        $response = RedsysRest::make(RESTTrataRequestService::class, $this->config->key)
            ->sendOperation($requestOperation);

        $result   = $response->getResult();
        Log::debug("[REDSYS] Getting refund response {$result}");
        if ($result == RESTConstants::$RESP_LITERAL_KO) {
            Log::error("[REDSYS] Operation `REFUND` was not OK");
            return new ChargeResult(false, $this->getResponse($response), $price->amount/100, $paymentReference);
        }
        return new ChargeResult(true, $this->getResponse($response), $price->amount/100, $paymentReference);
    }
}
