{block name="get_shipping_method"}
    {if isset($response)}
        {$response|@json_encode}
    {/if}
{/block}