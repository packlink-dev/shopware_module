{block name="get_tax_classes"}
    {if isset($response)}
        {$response|@json_encode}
    {/if}
{/block}