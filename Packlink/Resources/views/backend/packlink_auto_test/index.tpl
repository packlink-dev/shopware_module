{namespace name=backend/packlink/configuration}
{extends file="parent:backend/_base/layout.tpl"}

{block name="styles"}
    <link rel="stylesheet" href="{link file="backend/_resources/css/packlink-auto-test.css"}">
{/block}

{block name="scripts"}
    <script type="text/javascript" src="{link file="backend/_resources/js/UtilityService.js"}"></script>
    <script type="text/javascript" src="{link file="backend/_resources/js/AjaxService.js"}"></script>
    <script type="text/javascript" src="{link file="backend/_resources/js/AutoTestController.js"}"></script>
{/block}

{block name="content/main"}
    <div class="container-fluid pl-main-wrapper" id="pl-main-page-holder">
        <div class="pl-logo-wrapper">
            <img src="{link file="backend/_resources/images/logo-pl.svg"}"
                 class="pl-configuration-dashboard-logo"
                 alt="{s name="main/title"}Packlink PRO Shipping{/s}">
        </div>
        <div class="pl-auto-test-panel">
            <div class="pl-auto-test-header">
                <div class="pl-auto-test-title">
                    {s name="autotest/title"}PacklinkPRO module auto-test{/s}
                </div>
                <div class="pl-auto-test-subtitle">
                    {s name="autotest/description"}Use this page to test the system configuration and PacklinkPRO module services.{/s}
                </div>
            </div>

            <div class="pl-auto-test-content col-10" id="pl-auto-test-progress">
                <button type="button" name="start-test" id="pl-auto-test-start" class="button button-primary btn-lg">
                    {s name="autotest/start"}Start{/s}
                </button>
                <div class="pl-auto-test-log-panel" id="pl-auto-test-log-panel">
                    ...
                </div>
            </div>
            <div class="pl-auto-test-content" id="pl-spinner-box">
                <div class="pl-auto-spinner" id="pl-spinner">
                    <div></div>
                </div>
            </div>

            <div class="pl-auto-test-content" id="pl-auto-test-done">
                <div class="pl-auto-test-content col-10">
                    <div class="pl-flash-msg success" id="pl-flash-message-success">
                        <div class="pl-flash-msg-text-section">
                            <i class="material-icons success">
                                check_circle
                            </i>
                            <span id="pl-flash-message-text">
                                {s name="autotest/success"}Auto-test passed successfully!{/s}
						    </span>
                        </div>
                    </div>
                    <div class="pl-flash-msg danger" id="pl-flash-message-fail">
                        <div class="pl-flash-msg-text-section">
                            <i class="material-icons danger">
                                error
                            </i>
                            <span id="pl-flash-message-text">
                                {s name="autotest/fail"}The test did not complete successfully.{/s}
						    </span>
                        </div>
                    </div>
                </div>

                <a href="{url controller=PacklinkAutoTest action="logs" __csrf_token=$csrfToken}" value="auto-test-log.json" download>
                    <button type="button" name="download-log" class="button btn-info btn-lg">
                        {s name="autotest/download"}Download test log{/s}
                    </button>
                </a>
                <a href="{url controller=PacklinkDebug action="download" __csrf_token=$csrfToken}" value="packlink-debug-data.zip" download>
                    <button type="button" name="download-system-info-file" class="button btn-info btn-lg">
                        {s name="autotest/system/info"}Download system info file{/s}
                    </button>
                </a>
                <a href="{url controller=PacklinkConfiguration action="index" __csrf_token=$csrfToken}">
                    <button type="button" name="open-module" class="button btn-success btn-lg">
                        {s name="autotest/open"}Open PacklinkPRO module{/s}
                    </button>
                </a>

            </div>
        </div>
    </div>

    <script type="application/javascript">
        document.addEventListener('DOMContentLoaded', function () {
            Packlink.AutoTestController("{url controller=PacklinkAutoTest action="start" __csrf_token=$csrfToken}", "{url controller=PacklinkAutoTest action="status" __csrf_token=$csrfToken}");
        }, false);
    </script>
{/block}