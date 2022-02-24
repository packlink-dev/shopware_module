{block name="get_help_link"}
    {if isset($response)}
        {$response|@json_encode}
    {/if}
{/block}