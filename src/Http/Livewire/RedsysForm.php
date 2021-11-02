<?php

namespace Revosystems\Redsys\Http\Livewire;

use Livewire\Component;
use Revosystems\Redsys\Services\RedsysChargeRequest;
use Revosystems\Redsys\Models\PaymentHandler;
use Revosystems\Redsys\Models\RedsysPaymentGateway;
use Revosystems\Redsys\Services\RedsysCharge;

class RedsysForm extends Component
{
    protected $listeners = [
        'onCardFormSubmit',
        'onTokenizedCardPressed',
        'onPaymentCompleted',
    ];

    public $shouldSaveCard = false;
    public $orderReference;
    public $customerToken;
    public $iframeUrl;
    public $price;
    public $hasCards;
    public $redsysFormId;

    public function mount(string $redsysFormId, string $orderReference, string $price, string $customerToken, bool $hasCards)
    {
        $this->price            = $price;
        $this->redsysFormId     = $redsysFormId;
        $this->orderReference   = $orderReference;
        $this->customerToken    = $customerToken;
        $this->hasCards         = $hasCards;
        $this->iframeUrl        = RedsysPaymentGateway::isTestEnvironment() ? 'https://sis-t.redsys.es:25443/sis/NC/sandbox/redsysV2.js' : 'https://sis.redsys.es/sis/NC/redsysV2.js';
    }

    public function render()
    {
        return view('redsys::livewire.redsys-form');
    }

    public function onPaymentCompleted() : void
    {
        RedsysCharge::get($this->orderReference)->payHandler->onPaymentSucceed($this->orderReference);
    }
    
    public function onCardFormSubmit(string $operationId, array $extraInfo) : void
    {
        $redsysChargeRequest = RedsysChargeRequest::makeWithOperationId($this->orderReference, $operationId, $extraInfo);
        if ($this->shouldSaveCard) {
            $redsysChargeRequest->customerToken = $this->customerToken;
        }
        $this->emit('payResponse', RedsysPaymentGateway::get()->charge(
            RedsysCharge::get($this->orderReference),
            $redsysChargeRequest
        )->gatewayResponse);
    }

    public function onTokenizedCardPressed(string $cardId, array $extraInfo) : void
    {
        $redsysChargeRequest = RedsysChargeRequest::makeWithCard($this->orderReference, $cardId, $extraInfo);
        $this->emit('payResponse', RedsysPaymentGateway::get()->charge(
            RedsysCharge::get($this->orderReference),
            $redsysChargeRequest
        )->gatewayResponse);
    }
}
