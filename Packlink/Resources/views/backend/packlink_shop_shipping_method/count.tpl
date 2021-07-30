{block name="count"}
    {if isset($response)}
        {$response|@json_encode}
    {/if}
{/block}