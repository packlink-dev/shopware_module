{block name="deactivate_shop_shipping_methods"}
    {if isset($response)}
        {$response|@json_encode}
    {/if}
{/block}