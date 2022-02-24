{block name="search"}
    {if isset($response)}
        {$response|@json_encode}
    {/if}
{/block}