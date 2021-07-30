{block name="get_current_state"}
    {if isset($response)}
        {$response|@json_encode}
    {/if}
{/block}