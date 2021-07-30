{block name="get_status"}
    {if isset($response)}
        {$response|@json_encode}
    {/if}
{/block}