{* Packlink/Views/frontend/checkout/shipping_payment.tpl *}
{namespace name=frontend/packlink/dropoff}
{extends file="parent:frontend/checkout/shipping_payment.tpl"}

{block name="frontend_index_after_body"}
    <script src="{link file="backend/_resources/js/AjaxService.js"}"></script>
    <script src="{link file="backend/_resources/js/TemplateService.js"}"></script>
    <script src="{link file="backend/_resources/js/UtilityService.js"}"></script>
    <script src="{link file="backend/_resources/js/location/LocationPicker.js"}"></script>
    <script src="{link file="backend/_resources/js/location/Translations.js"}"></script>
    <link rel="stylesheet" href="{link file="backend/_resources/css/location/locationPicker.css"}" />
    <script src="{link file="frontend/_resources/js/services/CheckoutService.js"}"></script>
    <script src="{link file="frontend/_resources/js/CheckoutController.js"}"></script>
    <script src="{link file="frontend/_resources/js/MapModalController.js"}"></script>
    <script src="{link file="frontend/_resources/js/services/CheckoutService.js"}"></script>
    <link rel="stylesheet" href="{link file="frontend/_resources/css/checkout.css"}" />


    {$smarty.block.parent}

    <location-picker-template>
        <div class="lp-template" id="template-container">
            <div data-lp-id="working-hours-template" class="lp-hour-wrapper">
                <div class="day" data-lp-id="day">
                </div>
                <div class="hours" data-lp-id="hours">
                </div>
            </div>

            <div class="lp-location-wrapper" data-lp-id="location-template">
                <div class="composite lp-expand">
                    <div class="street-name uppercase" data-lp-id="composite-address"></div>
                    <div class="lp-working-hours-btn excluded" data-lp-composite data-lp-id="show-composite-working-hours-btn"></div>
                    <div data-lp-id="composite-working-hours" class="lp-working-hours">

                    </div>
                    <div class="lp-select-column">
                        <div class="lp-select-button excluded" data-lp-id="composite-select-btn"></div>
                        <a class="excluded" href="#" data-lp-id="composite-show-on-map" target="_blank"></a>
                    </div>
                </div>
                <div class="name uppercase lp-collapse" data-lp-id="location-name"></div>
                <div class="street lp-collapse">
                    <div class="street-name uppercase" data-lp-id="location-street"></div>
                    <div class="lp-working-hours-btn excluded" data-lp-id="show-working-hours-btn"></div>
                    <div data-lp-id="working-hours" class="lp-working-hours">

                    </div>
                </div>
                <div class="city uppercase lp-collapse" data-lp-id="location-city">
                </div>
                <div class="lp-select-column lp-collapse">
                    <div class="lp-select-button excluded" data-lp-id="select-btn"></div>
                </div>
                <a class="excluded lp-collapse" href="#" data-lp-id="show-on-map" target="_blank">
                    <div class="lp-show-on-map-btn excluded"></div>
                </a>
            </div>
        </div>
    </location-picker-template>

    <div class="pl-input-mask hidden" id="pl-map-modal">
        <div class="pl-map-modal" id="pl-modal-content">
            <div class="pl-modal-spinner-wrapper disabled" id="pl-modal-spinner">
                <div class="pl-modal-spinner"></div>
            </div>
            <div class="pl-close-modal" id="pl-close-modal-btn">X</div>

            <location-picker>
                <div class="lp-content" data-lp-id="content">
                    <div class="lp-locations">
                        <div class="lp-input-wrapper">
                            <div class="input">
                                <input type="text" data-lp-id="search-box" required="required" title=""/>
                                <span class="lp-label" data-lp-id="search-box-label"></span>
                            </div>
                        </div>

                        <div data-lp-id="locations"></div>
                    </div>
                </div>
            </location-picker>

        </div>
    </div>
{/block}

