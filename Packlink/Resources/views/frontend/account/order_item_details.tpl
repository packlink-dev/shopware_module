{* Packlink/Views/frontend/account/order_item_details.tpl *}
{namespace name=frontend/packlink/dropoff}
{extends file="parent:frontend/account/order_item_details.tpl"}

{* Shipping method label  *}
{block name="frontend_account_order_item_label_dispatch"}
    {$smarty.block.parent}

    {if $offerPosition.plHasDropoff}
        <p class="is--strong">{s name="deliveryAddress"}Delivery address{/s}</p>
    {/if}
{/block}

{* Shipping method *}
{block name='frontend_account_order_item_dispatch'}
    {$smarty.block.parent}

    {if $offerPosition.plHasDropoff}
        <p>{$offerPosition.plDropoffInfo}</p>
    {/if}
{/block}