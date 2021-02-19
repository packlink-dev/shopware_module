{namespace name=backend/packlink/configuration}
{extends file="parent:backend/_base/layout.tpl"}

{block name="styles"}
    <link rel="stylesheet" href="{link file="backend/_resources/packlink/css/app.css"}"/>
    <link rel="stylesheet" href="{link file="backend/_resources/css/font-awesome.min.css"}"/>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined"
          rel="stylesheet"/>
{/block}

{block name="scripts"}
    <script type="text/javascript" src="{link file="backend/_resources/packlink/js/TemplateService.js"}"></script>
    <script type="text/javascript" src="{link file="backend/_resources/packlink/js/TranslationService.js"}"></script>
    <script type="text/javascript" src="{link file="backend/_resources/packlink/js/AjaxService.js"}"></script>
    <script type="text/javascript" src="{link file="backend/_resources/packlink/js/AutoTestController.js"}"></script>
    <script type="text/javascript" src="{link file="backend/_resources/packlink/js/ConfigurationController.js"}"></script>
    <script type="text/javascript" src="{link file="backend/_resources/packlink/js/DefaultParcelController.js"}"></script>
    <script type="text/javascript" src="{link file="backend/_resources/packlink/js/DefaultWarehouseController.js"}"></script>
    <script type="text/javascript" src="{link file="backend/_resources/packlink/js/EditServiceController.js"}"></script>
    <script type="text/javascript" src="{link file="backend/_resources/packlink/js/LoginController.js"}"></script>
    <script type="text/javascript" src="{link file="backend/_resources/packlink/js/ModalService.js"}"></script>
    <script type="text/javascript" src="{link file="backend/_resources/packlink/js/MyShippingServicesController.js"}"></script>
    <script type="text/javascript" src="{link file="backend/_resources/packlink/js/OnboardingOverviewController.js"}"></script>
    <script type="text/javascript" src="{link file="backend/_resources/packlink/js/OnboardingStateController.js"}"></script>
    <script type="text/javascript" src="{link file="backend/_resources/packlink/js/OnboardingWelcomeController.js"}"></script>
    <script type="text/javascript" src="{link file="backend/_resources/packlink/js/OrderStatusMappingController.js"}"></script>
    <script type="text/javascript" src="{link file="backend/_resources/packlink/js/PageControllerFactory.js"}"></script>
    <script type="text/javascript" src="{link file="backend/_resources/packlink/js/PickShippingServiceController.js"}"></script>
    <script type="text/javascript" src="{link file="backend/_resources/packlink/js/PricePolicyController.js"}"></script>
    <script type="text/javascript" src="{link file="backend/_resources/packlink/js/RegisterController.js"}"></script>
    <script type="text/javascript" src="{link file="backend/_resources/packlink/js/RegisterModalController.js"}"></script>
    <script type="text/javascript" src="{link file="backend/_resources/packlink/js/ResponseService.js"}"></script>
    <script type="text/javascript" src="{link file="backend/_resources/packlink/js/ServiceCountriesModalController.js"}"></script>
    <script type="text/javascript" src="{link file="backend/_resources/packlink/js/ShippingServicesRenderer.js"}"></script>
    <script type="text/javascript" src="{link file="backend/_resources/packlink/js/StateController.js"}"></script>
    <script type="text/javascript" src="{link file="backend/_resources/packlink/js/StateUUIDService.js"}"></script>
    <script type="text/javascript" src="{link file="backend/_resources/packlink/js/SystemInfoController.js"}"></script>
    <script type="text/javascript" src="{link file="backend/_resources/packlink/js/UtilityService.js"}"></script>
    <script type="text/javascript" src="{link file="backend/_resources/packlink/js/ValidationService.js"}"></script>
    <script type="text/javascript" src="{link file="backend/_resources/packlink/js/GridResizerService.js"}"></script>
{/block}

{block name="content/main"}
    <div id="pl-page">
        <header id="pl-main-header">
            <div class="pl-main-logo">
                <img src="https://cdn.packlink.com/apps/giger/logos/packlink-pro.svg" alt="logo">
            </div>
            <div class="pl-header-holder" id="pl-header-section"></div>
        </header>

        <main id="pl-main-page-holder"></main>

        <div class="pl-spinner pl-hidden" id="pl-spinner">
            <div></div>
        </div>

        <template id="pl-alert">
            <div class="pl-alert-wrapper">
                <div class="pl-alert">
                    <span class="pl-alert-text"></span>
                    <i class="material-icons">close</i>
                </div>
            </div>
        </template>

        <template id="pl-modal">
            <div id="pl-modal-mask" class="pl-modal-mask pl-hidden">
                <div class="pl-modal">
                    <div class="pl-modal-close-button">
                        <i class="material-icons">close</i>
                    </div>
                    <div class="pl-modal-title">

                    </div>
                    <div class="pl-modal-body">

                    </div>
                    <div class="pl-modal-footer">
                    </div>
                </div>
            </div>
        </template>

        <template id="pl-error-template">
            <div class="pl-error-message" data-pl-element="error">
            </div>
        </template>
    </div>
{/block}

{block name="content/javascript"}
    <script type="text/javascript" src="{link file="backend/_resources/packlink/js/GridResizerService.js"}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            let ajaxService = Packlink.ajaxService;

            ajaxService.get(
                "{url controller=PacklinkConfiguration action="getData"}",
                function (response) {
                    Packlink.translations = {
                        default: response['lang']['default'],
                        current: response['lang']['current']
                    };

                    Packlink.state = new Packlink.StateController(
                        {
                            baseResourcesUrl: response['baseResourcesUrl'],
                            stateUrl: response['stateUrl'],
                            pageConfiguration: response['urls'],
                            templates: response['templates']
                        }
                    );

                    Packlink.state.display();

                    calculateContentHeight();
                }
            );

            /**
             * Calculates content height.
             */
            function calculateContentHeight() {
                let content = document.getElementById('pl-main-page-holder');
                let localOffset = content.offsetTop;

                let body = document.getElementsByTagName('body')[0];
                content.style.height = body.clientHeight - localOffset + 'px';

                setTimeout(calculateContentHeight, 250);
            }
        })
    </script>
{/block}