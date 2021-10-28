<div class="flex w-full flex-col space-y-4 justify-center md:justify-start">
    <div id="errorContainer" class="hidden flex items-center text-center text-white font-bold px-3 py-3 rounded shadow-xl bg-red-600" style="background-color: #e46e6a">
        <p id="errorMessage" class="flex text-m"></p>
    </div>
    @include('redsys::redsys.tokenized-cards', compact('cards', 'amount'))

    <x-redsys-radio-selector :id="'new-card-mode'" :name="'mode'" :label="__(config('redsys.translationsPrefix') . 'useNewCard')" :selected="$this->cards->isEmpty()">
        @include('redsys::redsys.iframe', compact('iframeUrl'))
    </x-redsys-radio-selector>

    @livewire('check-status', compact('orderReference'))

    {{--@livewire('apple-pay-button')--}}

    {{--@livewire('google-pay-button')--}}

    <x-redsys-radio-selector :id="'challenge-form-box'" :name="'challenge-form'" :label="'Redsys'" :hidden="true" :hideInput="true">
        <div id="challenge-form" class="block w-full h-16 flex-row justify-center text-center items-center outline-none rounded"></div>
    </x-redsys-radio-selector>
</div>
<script>
    function loadRedsysForm(buttonStyle = 'background-color:#E35732', bodyStyle = '', boxStyle = '', inputsStyle = '') {
        getInSiteForm('redsys-init-form', buttonStyle, bodyStyle, boxStyle, inputsStyle, "{!!  $this->buttonText . ' ' . $this->amount !!}",
            "{{ $this->merchantCode }}", "{{ $this->merchantTerminal }}", "{{ $this->orderReference }}", false)
    }

    function showErrorToast(message) {
        console.error(message)
        if (message) {
            document.getElementById("errorMessage").innerHTML = message
        }
        document.getElementById("errorContainer").classList.remove('hidden')
        setTimeout(function () {
            document.getElementById("errorContainer").classList.add('hidden')
        }, 3000);
    }

    function onCardFormError(errorCode) {
        window.livewire.emit('onCardFormSubmit', null, null, errorCode);
    }

    function onCardFormSuccess(operationId) {
        window.livewire.emit('onCardFormSubmit', operationId, browserData())
    }

    function onTokenizedCardPressed(cardId) {
        window.livewire.emit('onTokenizedCardPressed', cardId, browserData())
    }

    function browserData() {
        return {
            'browser_height' : screen.height,
            'browser_width' : screen.width,
            'browser_tz' : (new Date()).getTimezoneOffset(),
            'browser_color_depth' : screen.colorDepth
        }
    }

    function handleResponse(data) {
        console.debug(data)
        if (! data || ['OK', 'AUT'].indexOf(data.result) === -1) {
            showErrorToast("Something went wrong, redirecting…")
            return setTimeout(function () {
                location.reload();
            }, 3000)
        }
        if (data.result === 'AUT') {
            loadChallengeForm(data.displayForm)
            submitChallengeForm()
            return;
        }
        console.log('Emiting onPaymentCompleted event')
        window.livewire.emit("onPaymentCompleted")  // TODO: Add on payment completed event
    }

    function loadChallengeForm(displayForm) {
        console.debug(displayForm);
        Array.from(document.getElementsByClassName('radio-selector-box')).forEach((el) => { el.classList.contains('hidden') ? el.classList.remove('hidden') : el.classList.add('hidden') })
        document.getElementById("challenge-form-box").classList.remove('hidden')
        document.getElementById("challenge-form").style.height = '980px'
        document.getElementById("challenge-form").innerHTML = displayForm
        document.getElementById("challenge-form").click()
    }

    function submitChallengeForm() {
        document.getElementById("redsys_iframe_acs").onload = function() {
            document.getElementById("redsysAcsForm").style.display = "none";
            document.getElementById("redsys_iframe_acs").style.display = "inline";
        }
        document.getElementById("redsysAcsForm").submit();
    }

    window.addEventListener("message", function receiveMessage(event) {
        storeIdOper(event, "token", "errorCode", function merchantValidation() { return true });
        if (event.data.error || event.data.idOper === -1) {
            onCardFormError( event.data.error );
            return;
        }
        if (event.data.idOper && event.data.idOper !== -1) {
            onCardFormSuccess( event.data.idOper );
        }
    });

    document.addEventListener("DOMContentLoaded", function(event) {
        window.livewire.on('showErrorEvent', function (formError) {
            showErrorToast(formError);
        })
        window.livewire.on('payResponse', function (data) {
            handleResponse(data);
        })
        loadRedsysForm()
    })
</script>