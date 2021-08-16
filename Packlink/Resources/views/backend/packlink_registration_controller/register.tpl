{block name="register"}
    {if isset($response)}
        {$response|@json_encode}
    {/if}
{/block}