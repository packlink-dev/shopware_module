{block name="deactivate"}
    {if isset($response)}
        {$response|@json_encode}
    {/if}
{/block}