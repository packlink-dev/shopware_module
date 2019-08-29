var Packlink = window.Packlink || {};

(function () {
    Packlink.ConfirmService = function () {
        let constructor = function () {};
        constructor.prototype = Object.create(Packlink.CheckoutService.prototype);

        constructor.prototype.getExtensionPoint = function () {
            return document.getElementById('pl-dropoff-extension-point');
        };

        constructor.prototype.getTemplate = function (config) {
            return  '<div class="pl-payment-section-selector-wrapper">' +
                     '<div id="pl-select-button" class="btn is--primary is--small">' + config.buttonLabel  + '</i></div>' +

                    '<div class="pl-tooltip">' +
                       '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="22px" height="22px" viewBox="0 0 15 15" version="1.1">' +
                            '<g id="AddressDetails-billingAddress-withWrongVat-feedback" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd" transform="translate(-527.000000, -161.000000)">' +
                                '<g id="Group-3" transform="translate(517.000000, 151.000000)" fill="#5d5d5d">' +
                                    '<g id="info" transform="translate(10.000000, 10.000000)">' +
                                        '<path d="M7.5,14.3181818 C11.2655778,14.3181818 14.3181818,11.2655778 14.3181818,7.5 C14.3181818,3.73442216 11.2655778,0.681818182 7.5,0.681818182 C3.73442216,0.681818182 0.681818182,3.73442216 0.681818182,7.5 C0.681818182,11.2655778 3.73442216,14.3181818 7.5,14.3181818 Z M7.5,15 C3.35786438,15 0,11.6421356 0,7.5 C0,3.35786438 3.35786438,0 7.5,0 C11.6421356,0 15,3.35786438 15,7.5 C15,11.6421356 11.6421356,15 7.5,15 Z" id="Oval-3" fill-rule="nonzero"/>' +
                                        '<path d="M7.35818182,4.88727273 C7.66363636,4.88727273 7.91454545,4.64727273 7.91454545,4.34181818 C7.91454545,4.03636364 7.66363636,3.78545455 7.35818182,3.78545455 C7.06363636,3.78545455 6.81272727,4.03636364 6.81272727,4.34181818 C6.81272727,4.64727273 7.06363636,4.88727273 7.35818182,4.88727273 Z M7.77272727,10.9090909 L7.77272727,5.64 L6.95454545,5.64 L6.95454545,10.9090909 L7.77272727,10.9090909 Z" id="i"/>' +
                                    '</g>' +
                                '</g>' +
                            '</g>' +
                        '</svg>' +

                        '<span class="pl-tooltip-concrete pl-tooltip-top">' +
                            config.description +
                        '</span>' +
                    '</div>' +
                '</div>' +
                (config.isSelected ? ('<p><strong>' + config.selectedDescription + '</strong><i>' + config.selectedAddress + '</i></p>') : '') +
                (config.isError ? ('<p><i>' + config.error + '</i></p>') : '');
        };

        return new constructor;
    }
})();