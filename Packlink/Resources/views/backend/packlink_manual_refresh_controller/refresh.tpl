{block name="refresh"}
    {if isset($response)}
        {$response|@json_encode}
    {/if}
{/block}