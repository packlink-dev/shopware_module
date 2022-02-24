{block name="create"}
    {if isset($response)}
        {$response|@json_encode}
    {/if}
{/block}