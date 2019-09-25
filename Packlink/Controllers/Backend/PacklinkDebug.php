<?php

use Packlink\Controllers\Common\CanInstantiateServices;
use Packlink\Utilities\Request;
use Packlink\Utilities\Response;

class Shopware_Controllers_Backend_PacklinkDebug extends Enlight_Controller_Action
{
    use CanInstantiateServices;
    const SYSTEM_INFO_FILE_NAME = 'packlink-debug-data.zip';

    /**
     * Retrieves debug mode status.
     */
    public function getStatusAction()
    {
        Response::json(['status' => $this->getConfigService()->isDebugModeEnabled()]);
    }

    /**
     * Sets debug mode status.
     */
    public function updateStatusAction()
    {
        $data = Request::getPostData();
        if (!isset($data['status']) || !is_bool($data['status'])) {
            Response::json(['success' => false], 400);
        }

        $this->getConfigService()->setDebugModeEnabled($data['status']);

        Response::json(['status' => $data['status']]);
    }


    public function downloadAction()
    {
        $service = $this->getDebugService();
        $file = $service::getSystemInfo();

        Response::file($file, self::SYSTEM_INFO_FILE_NAME);
    }
}