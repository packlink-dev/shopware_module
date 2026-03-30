{block name="index"}
    {if isset($response)}
        {$response|@json_encode}
    {/if}
{/block}