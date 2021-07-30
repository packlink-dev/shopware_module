{block name="list"}
    {if isset($response)}
        {$response|@json_encode}
    {/if}
{/block}