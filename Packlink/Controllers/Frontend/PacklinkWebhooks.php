<?php

use Packlink\BusinessLogic\WebHook\WebHookEventHandler;
use Packlink\Utilities\Response;
use Shopware\Components\CSRFWhitelistAware;

class Shopware_Controllers_Frontend_PacklinkWebhooks extends Enlight_Controller_Action implements CSRFWhitelistAware
{
    /**
     * Returns a list with actions which should not be validated for CSRF protection
     *
     * @return string[]
     */
    public function getWhitelistedCSRFActions()
    {
        return ['index'];
    }

    /**
     * Handles packlink webhooks.
     */
    public function indexAction()
    {
        $input = file_get_contents('php://input');
        $handler = WebHookEventHandler::getInstance();

        if (!$handler->handle($input)) {
            Response::json(['message' => 'Invalid payload'], 400);
        }

        Response::json(['success' => true]);
    }
}