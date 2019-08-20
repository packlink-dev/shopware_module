<?php

namespace Packlink\Controllers\Common;

use Logeecom\Infrastructure\Configuration\Configuration;
use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\Http\Proxy;
use Packlink\BusinessLogic\Location\LocationService;
use Packlink\BusinessLogic\User\UserAccountService;
use Packlink\Contracts\Services\BusinessLogic\DebugService;

trait CanInstantiateServices
{
    /** @var \Packlink\Services\BusinessLogic\ConfigurationService */
    protected $configService;
    /** @var \Packlink\BusinessLogic\User\UserAccountService */
    protected $userAccountService;
    /** @var \Packlink\BusinessLogic\Location\LocationService */
    protected $locationService;
    /** @var \Packlink\BusinessLogic\Http\Proxy */
    protected $proxy;
    /** @var \Packlink\Contracts\Services\BusinessLogic\DebugService */
    protected $debugService;

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

    /**
     * Retrieves location service.
     *
     * @return \Packlink\BusinessLogic\Location\LocationService
     */
    protected function getLocationService()
    {
        if ($this->locationService === null) {
            $this->locationService = ServiceRegister::getService(LocationService::CLASS_NAME);
        }

        return $this->locationService;
    }

    /**
     * Retrieves proxy.
     *
     * @return \Packlink\BusinessLogic\Http\Proxy
     */
    protected function getProxy()
    {
        if ($this->proxy === null) {
            $this->proxy = ServiceRegister::getService(Proxy::CLASS_NAME);
        }

        return $this->proxy;
    }

    /**
     * Retrieves debug service.
     *
     * @return \Packlink\Contracts\Services\BusinessLogic\DebugService
     */
    protected function getDebugService()
    {
        if ($this->debugService === null) {
            $this->debugService = ServiceRegister::getService(DebugService::class);
        }

        return $this->debugService;
    }
}