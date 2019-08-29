{* Packlink/Views/frontend/checkout/shipping_payment.tpl *}
{namespace name=frontend/packlink/dropoff}
{extends file="parent:frontend/checkout/shipping_payment.tpl"}

{block name="frontend_index_after_body"}
    {include file="frontend/packlink_locationpicker/location_picker_assets.tpl"}

    {$smarty.block.parent}

    {include file="frontend/packlink_locationpicker/location_picker.tpl"}
{/block}

