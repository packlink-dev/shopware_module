<?php

use Packlink\BusinessLogic\Controllers\DebugController;
use Packlink\Controllers\Common\CanInstantiateServices;
use Packlink\Utilities\Request;
use Packlink\Utilities\Response;
use Packlink\Utilities\Url;

class Shopware_Controllers_Backend_PacklinkDebug extends Enlight_Controller_Action
{
    use CanInstantiateServices;
    const SYSTEM_INFO_FILE_NAME = 'packlink-debug-data.zip';
    /**
     * @var DebugController
     */
    private $baseController;

    /**
     * Retrieves debug mode status.
     */
    public function getStatusAction()
    {
        Response::json([
            'status' => $this->getBaseController()->getStatus(),
            'downloadUrl' => Url::getBackendUrl('PacklinkDebug', 'download'),
        ]);
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

        $this->getBaseController()->setStatus((bool)$data['status']);

        Response::json(['status' => $data['status']]);
    }


    public function downloadAction()
    {
        $service = $this->getDebugService();
        $file = $service::getSystemInfo();

        Response::file($file, self::SYSTEM_INFO_FILE_NAME);
    }

    /**
     * @return DebugController
     */
    protected function getBaseController()
    {
        if ($this->baseController === null) {
            $this->baseController =  new DebugController();
        }

        return $this->baseController;
    }
}
