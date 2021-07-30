{block name="login"}
    {if isset($response)}
        {$response|@json_encode}
    {/if}
{/block}