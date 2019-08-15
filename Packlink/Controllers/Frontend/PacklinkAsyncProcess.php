<?php

use Logeecom\Infrastructure\Logger\Logger;
use Logeecom\Infrastructure\ServiceRegister;
use Logeecom\Infrastructure\TaskExecution\Interfaces\AsyncProcessService;
use Packlink\Bootstrap\Bootstrap;
use Packlink\Utilities\Response;
use Shopware\Components\CSRFWhitelistAware;

class Shopware_Controllers_Frontend_PacklinkAsyncProcess extends Enlight_Controller_Action implements CSRFWhitelistAware
{
    /**
     * Returns a list with actions which should not be validated for CSRF protection
     *
     * @return string[]
     */
    public function getWhitelistedCSRFActions()
    {
        return ['run'];
    }

    /**
     * Starts async process.
     */
    public function runAction()
    {
        $guid = $this->Request()->getParam('guid', '');

        Logger::logDebug("Received async process request with guid [{$guid}].");

        /** @var AsyncProcessService $asyncProcessService */
        $asyncProcessService = ServiceRegister::getService(AsyncProcessService::CLASS_NAME);
        $asyncProcessService->runProcess($guid);

        Response::json(['success' => true]);
    }
}