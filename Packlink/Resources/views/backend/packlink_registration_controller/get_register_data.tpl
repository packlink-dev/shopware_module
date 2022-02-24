{block name="get_register_data"}
    {if isset($response)}
        {$response|@json_encode}
    {/if}
{/block}