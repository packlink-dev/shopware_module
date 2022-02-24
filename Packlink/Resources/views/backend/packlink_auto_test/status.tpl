{block name="status"}
    {if isset($response)}
        {$response|@json_encode}
    {/if}
{/block}