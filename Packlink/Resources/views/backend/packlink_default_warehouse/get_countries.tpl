{block name="get_countries"}
    {if isset($response)}
        {$response|@json_encode}
    {/if}
{/block}