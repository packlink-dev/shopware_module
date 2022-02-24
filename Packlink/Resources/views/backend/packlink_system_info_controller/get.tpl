{block name="get"}
    {if isset($response)}
        {$response|@json_encode}
    {/if}
{/block}