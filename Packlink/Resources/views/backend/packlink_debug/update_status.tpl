{block name="update_status"}
    {if isset($response)}
        {$response|@json_encode}
    {/if}
{/block}