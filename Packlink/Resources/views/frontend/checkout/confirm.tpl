{* Packlink/Views/frontend/checkout/confirm.tpl *}
{namespace name=frontend/packlink/dropoff}
{extends file="parent:frontend/checkout/confirm.tpl"}

{block name="frontend_index_after_body"}
    {include file="frontend/packlink_locationpicker/location_picker_assets.tpl"}

    {$smarty.block.parent}

    {include file="frontend/packlink_locationpicker/location_picker.tpl"}
{/block}

{block name='frontend_checkout_confirm_left_shipping_method'}
    {$smarty.block.parent}

    <div id="pl-dropoff-extension-point"></div>
{/block}

{block name='frontend_index_content'}
    {include file="frontend/packlink_locationpicker/alert_messages.tpl"}

    {$smarty.block.parent}

    <script>
        let plTranslations = {
            selectDropoffDescription: '{s name="selectDropoffDescription"}This shipping service supports delivery to pre-defined drop-off locations. Please choose location that suits you the most by clicking on the "Select drop-off location" button.{/s}',
            selectDropoffBtnLabel: '{s name="selectDropoffBtnLabel"}Select drop-off location{/s}',
            noLocations: '{s name="noLocations"}There are no delivery locations available for your delivery address. Please change your address.{/s}',
            selectedAddress: '{s name="selectedAddress"}Package will be delivered to:{/s}'
        };

        let plConfig = JSON.parse('{$plConfig}'.replace(/&quot;/g, '"').replace(/&amp;/g, '&'));
        plConfig.currentDispatch = '{$sDispatch.id}';
        plConfig.getLocationsUrl = '{url controller=PacklinkLocations action="list" __csrf_token=$plCsrf}';
        plConfig.updateDropoffUrl = '{url controller=PacklinkDropoff action="update" __csrf_token=$plCsrf}';
        let plController = new Packlink.CheckoutController(Packlink.ConfirmService(), plConfig, plTranslations);
        plController.init();
    </script>
{/block}