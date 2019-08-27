var Packlink = window.Packlink || {};

(function () {
    function CheckoutControllerConstructor(shippingService, configuration, translations) {
        this.init = init;
        let service = shippingService;
        let dropoffs = [];
        let mapController;

        function init() {
            dropoffs = Object.keys(configuration.dropOffs);
            if (isDropoff(configuration.currentDispatch)) {
                showDropoffSelector();
            }
        }

        function showDropoffSelector() {
            if (configuration.dropOff.isSelected) {
                service.addDropoffSelector(
                    configuration.currentDispatch,
                    {
                        isSelected: true,
                        description: translations.selectDropoffDescription,
                        buttonLabel: translations.selectDropoffBtnLabel,
                        selectedDescription: translations.selectedAddress,
                        selectedAddress: getAddress(configuration.dropOff.selectedDropoff)
                    },
                    onSelectDropoffClicked
                );
            } else {
                service.disableContinue();
                service.addDropoffSelector(
                    configuration.currentDispatch,
                    {
                        isSelected: false,
                        description: translations.selectDropoffDescription,
                        buttonLabel: translations.selectDropoffBtnLabel
                    },
                    onSelectDropoffClicked
                );
            }
        }

        function onSelectDropoffClicked() {
            mapController = new Packlink.MapModalController(
                {
                    getUrl: configuration.getLocationsUrl,
                    updateUrl: configuration.updateDropoffUrl,
                    methodId: configuration.dropOffs[configuration.currentDispatch],
                    carrierId: configuration.currentDispatch,
                    onComplete: modalCompleteCallback,
                    dropOffId: configuration.dropOff.isSelected ? configuration.dropOff.selectedDropoff.id : null,
                    lang: configuration.lang
                }
            );

            mapController.display();
        }

        function modalCompleteCallback(payload) {
            mapController.close();
            mapController = null;

            if (payload.type === 'no-locations') {
                service.addDropoffSelector(
                    configuration.currentDispatch,
                    {
                        isSelected: false,
                        description: translations.selectDropoffDescription,
                        buttonLabel: translations.selectDropoffBtnLabel,
                        isError: true,
                        error: translations.noLocations
                    },
                    onSelectDropoffClicked
                );
            }

            if (payload.type === 'success') {
                service.addDropoffSelector(
                    configuration.currentDispatch,
                    {
                        isSelected: true,
                        description: translations.selectDropoffDescription,
                        buttonLabel: translations.selectDropoffBtnLabel,
                        selectedDescription: translations.selectedAddress,
                        selectedAddress: payload.address
                    },
                    onSelectDropoffClicked
                );

                service.enableContinue();
            }
        }

        /**
         * Returns formatted address.
         *
         * @param {object} location
         * @return {string}
         */
        function getAddress(location) {
            return location['name'] + ', ' + location['address'] + ', ' + location['zip'] + ', ' + location['city'];
        }

        /**
         * Checks if carrier is dropoff.
         *
         * @param {string} id
         *
         * @return {boolean}
         */
        function isDropoff(id) {
            return dropoffs.indexOf(id) !== -1;
        }
    }

    Packlink.CheckoutController = CheckoutControllerConstructor;
})();