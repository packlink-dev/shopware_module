<!DOCTYPE html>
<html lang="en">
<head>
    <title>{s name="main/title"}Packlink PRO Shipping{/s}</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script type="text/javascript" src="{link file="backend/base/frame/postmessage-api.js"}"></script>
    <link rel="stylesheet" href="{link file="backend/_resources/css/packlink.css"}">
    {block name="scripts"}{/block}
</head>
<body role="document" style="padding-top: 17px">


<div class="container-fluid pl-main-wrapper" id="pl-main-page-holder">
    {block name="content/main"}{/block}
</div>

{block name="content/layout/javascript"}
<script type="text/javascript">
    (function(window) {
        window.events.subscribe('initialized-api', function(obj) {
            // Do something now that the event has occurred
        });
    }(window));
</script>
{/block}
{block name="content/javascript"}{/block}
</body>
</html>