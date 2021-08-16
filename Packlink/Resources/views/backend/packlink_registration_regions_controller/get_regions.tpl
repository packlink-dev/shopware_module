{block name="get_regions"}
    {if isset($response)}
        {$response|@json_encode}
    {/if}
{/block}