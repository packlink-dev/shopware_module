<?php

use Packlink\BusinessLogic\Controllers\DebugController;
use Packlink\Controllers\Common\CanInstantiateServices;
use Packlink\Utilities\Request;
use Packlink\Utilities\Response;

class Shopware_Controllers_Backend_PacklinkDebug extends Enlight_Controller_Action
{
    use CanInstantiateServices;
    const SYSTEM_INFO_FILE_NAME = 'packlink-debug-data.zip';
    /**
     * @var DebugController
     */
    private $baseController;

    public function __construct()
    {
        parent::__construct();
        $this->baseController = new DebugController();
    }

    /**
     * Retrieves debug mode status.
     */
    public function getStatusAction()
    {
        Response::json(['status' => $this->baseController->getStatus()]);
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

        $this->baseController->setStatus((bool)$data['status']);

        Response::json(['status' => $data['status']]);
    }


    public function downloadAction()
    {
        $service = $this->getDebugService();
        $file = $service::getSystemInfo();

        Response::file($file, self::SYSTEM_INFO_FILE_NAME);
    }
}
