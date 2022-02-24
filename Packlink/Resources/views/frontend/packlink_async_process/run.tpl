{block name="run"}
    {if isset($response)}
        {$response|@json_encode}
    {/if}
{/block}