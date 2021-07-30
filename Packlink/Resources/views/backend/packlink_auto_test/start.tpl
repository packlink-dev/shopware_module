{block name="start"}
    {if isset($response)}
        {$response|@json_encode}
    {/if}
{/block}