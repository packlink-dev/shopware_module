{namespace name=backend/packlink/configuration}
{extends file="parent:backend/_base/layout.tpl"}

{block name="scripts"}
    <script type="text/javascript" src="{link file="backend/_resources/js/UtilityService.js"}"></script>
{/block}

{block name="content/main"}
    <div class="pl-login-page" id="pl-main-page-holder">
        <div class="pl-login-page-side-img-wrapper pl-collapse">
            <img src="{link file="backend/_resources/images/logo-pl.svg"}"
                 class="pl-dashboard-logo"
                 alt="{s name="main/title"}Packlink PRO Shipping{/s}">
        </div>
        <div class="pl-login-page-content-wrapper">
            <div class="pl-register-form-wrapper">
                <div class="pl-register-btn-section-wrapper">
                    {s name="login/noaccount"}Don't have an account?{/s}
                    <button type="button" id="pl-register-btn" class="button button-primary button-register">
                        {s name="login/register"}Register{/s}
                    </button>
                </div>
                <div class="pl-register-country-section-wrapper" id="pl-register-form" style="display: none;">
                    <div class="pl-register-form-close-btn">
                        <svg id="pl-register-form-close-btn" width="24" height="24" viewBox="0 0 22 22"
                             xmlns="http://www.w3.org/2000/svg">
                            <g fill="none" fill-rule="evenodd">
                                <path d="M11 21c5.523 0 10-4.477 10-10S16.523 1 11 1 1 5.477 1 11s4.477 10 10 10zm0 1C4.925 22 0 17.075 0 11S4.925 0 11 0s11 4.925 11 11-4.925 11-11 11z"
                                      fill="#2095F2" fill-rule="nonzero"/>
                                <path d="M7.5 7.5l8 7M15.5 7.5l-8 7" stroke="#2095F2" stroke-linecap="square"/>
                            </g>
                        </svg>
                    </div>
                    <div class="pl-register-country-title-wrapper">
                        {s name="login/country"}Select country to start{/s}
                    </div>
                    <div class="pl-register-country-list-wrapper">
                        {foreach $countries as $country}
                            <a href="{html_entity_decode($country->registrationLink|escape:'html':'UTF-8')}" target="_blank">
                                <div class="pl-country">
                                    <img
                                            src="{link file="backend/_resources/images/flags/{$country->code}.svg"}"
                                            class="pl-country-logo"
                                            alt="{$country->name}"
                                    >
                                    <div class="pl-country-name">{$country->name}</div>
                                </div>
                            </a>
                        {/foreach}
                    </div>
                </div>
            </div>
            <div>
                <div class="pl-login-form-header">
                    <div class="pl-login-form-title-wrapper">
                        {s name="login/allow"}Allow Shopware to connect to PacklinkPRO{/s}
                    </div>
                    <div class="pl-login-form-text-wrapper">
                        {s name="login/findapikey"}Your API key can be found under{/s}
                        pro.packlink/<strong>Settings/PacklinkProAPIkey</strong>
                    </div>
                </div>
                {if $isLoginFailure}
                <p class="pl-login-error-msg">
                    {s name="login/incorrectapikey"}API key was incorrect.{/s}
                </p>
                {/if}
                <div class="pl-login-form-label-wrapper">
                    {s name="login/connectaccount"}Connect your account{/s}
                </div>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="{$csrfToken}" />
                    <div class="pl-login-form-wrapper">
                        <fieldset class="form-group pl-form-section-input pl-text-input">
                            <input type="text" class="form-control" id="pl-login-api-key" name="api_key" required/>
                            <span class="pl-text-input-label">
                                {s name="login/apikey"}Api key{/s}
                            </span>
                        </fieldset>
                    </div>
                    <div>
                        <button type="submit" name="login"
                                class="button button-primary button-login">
                            {s name="login/login"}Log in{/s}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
{/block}

{block name="content/javascript"}
    <script>
        function initRegisterForm() {
            let registerBtnClicked = function (event) {
                event.stopPropagation();
                let form = document.getElementById('pl-register-form');
                form.style.display = 'block';

                let closeBtn = document.getElementById('pl-register-form-close-btn');
                closeBtn.addEventListener('click', function () {
                    form.style.display = 'none';
                });

                let container = document.querySelector('.pl-login-page-content-wrapper');
                container.addEventListener('click', function () {
                    form.style.display = 'none';
                });
            };

            let btn = document.getElementById('pl-register-btn');
            btn.addEventListener('click', registerBtnClicked, true);
        }

        initRegisterForm();
        Packlink.utilityService.configureInputElements();
    </script>
{/block}