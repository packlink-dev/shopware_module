{block name="get_inactive"}
    {if isset($response)}
        {$response|@json_encode}
    {/if}
{/block}