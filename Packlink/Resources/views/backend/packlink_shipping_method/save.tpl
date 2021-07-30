{block name="save"}
    {if isset($response)}
        {$response|@json_encode}
    {/if}
{/block}