{namespace name=backend/packlink/configuration}
{extends file="parent:backend/_base/layout.tpl"}

{block name="content/main"}
    <div class="pl-page-wrapper">
        <div class="pl-sidebar-wrapper">
            <div class="pl-logo-wrapper">
                <img src="{link file="backend/_resources/images/logo-pl.svg"}"
                     class="pl-dashboard-logo"
                     alt="{s name="main/title"}Packlink PRO Shipping{/s}">
            </div>
        </div>
        <a href="{url controller=PacklinkMain action="index" __csrf_token=$csrfToken}">Test link generation!</a>
    </div>
{/block}