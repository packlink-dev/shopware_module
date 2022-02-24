{block name="activate"}
    {if isset($response)}
        {$response|@json_encode}
    {/if}
{/block}