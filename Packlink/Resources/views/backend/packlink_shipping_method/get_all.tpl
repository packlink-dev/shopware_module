{block name="get_all"}
    {if isset($response)}
        {$response|@json_encode}
    {/if}
{/block}