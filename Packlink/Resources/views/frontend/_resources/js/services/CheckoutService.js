var Packlink = window.Packlink || {};

(function () {
    let constructor = function () {};

    constructor.prototype.addDropoffSelector = function (dropoff, config, selectCallback) {
        this.removeSelector();
        let extensionPoint = this.getExtensionPoint(dropoff);
        extensionPoint.innerHTML = this.getTemplate(config);
        this.getSelectButton().onclick = selectCallback;
    };

    constructor.prototype.removeSelector = function () {
        let selector = this.getSelector();

        if (selector) {
            selector.remove();
        }
    };

    constructor.prototype.getSelector = function () {
        return document.getElementById('pl-selector');
    };

    constructor.prototype.getExtensionPoint = function (id) {
        return document.getElementById('pl-dropoff-extension-point-' + id);
    };

    constructor.prototype.getTemplate = function (config) {
        return '<div class="method--description">' +
                '<p>' + config.description + '</p>' +
                '<div style="display: flex;justify-content: space-between;">' +
                    (config.isSelected ? ('<p>' + config.selectedDescription + '</br><i>' + config.selectedAddress + '</i></p>') : '') +
                    (config.isError ? ('<p>' + config.error + '</p>') : '') +
                    '<div id="pl-select-button" class="btn is--primary is--large right" style="margin-left: auto">' + config.buttonLabel + '</i></div>' +
                '</div>' +
            '</div>';
    };

    constructor.prototype.getSelectButton = function () {
        return document.getElementById('pl-select-button');
    };

    constructor.prototype.disableContinue = function () {
        let continueButtons = this.getContinueButtons();
        for (let btn of continueButtons) {
            btn.disabled = true;
        }
    };

    constructor.prototype.enableContinue = function () {
        let ctnBtns = this.getContinueButtons();
        for (let btn of ctnBtns) {
            btn.disabled = false;
        }
    };

    constructor.prototype.removeAlerts = function () {
        let alert = this.getAlert();

        if (alert) {
            alert.remove();
        }
    };

    constructor.prototype.getAlert = function () {
        return document.getElementById('pl-not-selected-dropoff-alert');
    };

    constructor.prototype.getContinueButtons = function () {
        return document.querySelectorAll('button.main--actions, div.main--actions > button[type="submit"]');
    };

    Packlink.CheckoutService = constructor;
})();