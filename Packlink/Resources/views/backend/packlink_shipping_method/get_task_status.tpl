{block name="get_task_status"}
    {if isset($response)}
        {$response|@json_encode}
    {/if}
{/block}