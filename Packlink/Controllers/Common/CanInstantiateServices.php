<?php

namespace Packlink\Controllers\Common;

use Logeecom\Infrastructure\Configuration\Configuration;
use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\User\UserAccountService;

trait CanInstantiateServices
{
    /** @var \Packlink\Services\BusinessLogic\ConfigurationService */
    protected $configService;
    /** @var \Packlink\BusinessLogic\User\UserAccountService */
    protected $userAccountService;

    /**
     * Retrieves configuration service.
     *
     * @return \Packlink\Services\BusinessLogic\ConfigurationService
     */
    protected function getConfigService()
    {
        if ($this->configService === null) {
            $this->configService = ServiceRegister::getService(Configuration::CLASS_NAME);
        }

        return $this->configService;
    }

    /**
     * Retrieves user account service.
     *
     * @return \Packlink\BusinessLogic\User\UserAccountService
     */
    protected function getUserAccountService()
    {
        if ($this->userAccountService === null) {
            $this->userAccountService = ServiceRegister::getService(UserAccountService::CLASS_NAME);
        }

        return $this->userAccountService;
    }
}