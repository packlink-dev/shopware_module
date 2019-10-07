{* Packlink/Views/frontend/checkout/change_shipping.tpl *}
{namespace name=frontend/packlink/dropoff}
{extends file="parent:frontend/checkout/change_shipping.tpl"}
{* Method Description *}
{block name='frontend_checkout_shipping_fieldset_description'}
    {$smarty.block.parent}
    <div id="pl-dropoff-extension-point-{$dispatch.id}"></div>
{/block}

{* Radio Button *}
{block name='frontend_checkout_dispatch_shipping_input_radio'}
    {$smarty.block.parent}
    {if $dispatch.isPlLogoEnabled}
        <div class="pl-logo right">
            <img src="{$dispatch.plLogo}" alt="Logo">
        </div>
    {/if}
{/block}


{block name="frontend_checkout_shipping_content"}
    {$smarty.block.parent}
    {if $plIsLoggedIn}
        <script>
            (function () {
                let plTranslations = {
                    selectDropoffDescription: '{s name="selectDropoffDescription"}This shipping service supports delivery to pre-defined drop-off locations. Please choose location that suits you the most by clicking on the "Select drop-off location" button.{/s}',
                    selectDropoffBtnLabel: '{s name="selectDropoffBtnLabel"}Select drop-off location{/s}',
                    noLocations: "{s name="noLocations"}There are no delivery locations available for your delivery address. Please change your address.{/s}",
                    selectedAddress: '{s name="selectedAddress"}Package will be delivered to:{/s}'
                };

                let plConfig = JSON.parse('{$plConfig}'.replace(/&quot;/g, '"').replace(/&amp;/g, '&'));
                plConfig.currentDispatch = '{$sDispatch.id}';
                plConfig.getLocationsUrl = '{url controller=PacklinkLocations action="list" __csrf_token=$plCsrf}';
                plConfig.updateDropoffUrl = '{url controller=PacklinkDropoff action="update" __csrf_token=$plCsrf}';
                plConfig.lang = '{$plLang}';

                let plController = new Packlink.CheckoutController(new Packlink.CheckoutService(), plConfig, plTranslations);

                if (Packlink.isLoaded) {
                    plController.init();
                } else {
                    document.addEventListener('DOMContentLoaded', function () {
                        Packlink.isLoaded = true;
                        plController.init();
                    })
                }
            })();
        </script>
    {/if}
{/block}