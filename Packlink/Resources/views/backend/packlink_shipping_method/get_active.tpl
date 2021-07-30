{block name="get_active"}
    {if isset($response)}
        {$response|@json_encode}
    {/if}
{/block}