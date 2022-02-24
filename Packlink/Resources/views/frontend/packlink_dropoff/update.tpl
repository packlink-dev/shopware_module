{block name="update"}
    {if isset($response)}
        {$response|@json_encode}
    {/if}
{/block}