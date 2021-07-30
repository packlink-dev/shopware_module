{block name="get_data"}
    {if isset($response)}
        {$response|@json_encode}
    {/if}
{/block}