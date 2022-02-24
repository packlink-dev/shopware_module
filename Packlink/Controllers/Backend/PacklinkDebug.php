<?php

use Packlink\BusinessLogic\Controllers\DebugController;
use Packlink\Controllers\Common\CanInstantiateServices;
use Packlink\Utilities\Request;
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
     * Disable template engine for download action.
     *
     * @throws Exception
     */
    public function preDispatch()
    {
        if($this->Request()->getActionName() === 'download') {
            $this->Front()->Plugins()->ViewRenderer()->setNoRender();
        }
    }

    /**
     * Retrieves debug mode status.
     */
    public function getStatusAction()
    {
        $this->View()->assign(
            'response',
            [
                'status' => $this->getBaseController()->getStatus(),
                'downloadUrl' => Url::getBackendUrl('PacklinkDebug', 'download'),
            ]
        );
    }

    /**
     * Sets debug mode status.
     */
    public function updateStatusAction()
    {
        $data = Request::getPostData();
        if (!isset($data['status']) || !is_bool($data['status'])) {
            $this->Response()->setStatusCode(400);
            $this->View()->assign('response', ['success' => false]);

            return;
        }

        $this->getBaseController()->setStatus((bool)$data['status']);

        $this->View()->assign('response', ['status' => $data['status']]);
    }


    public function downloadAction()
    {
        $service = $this->getDebugService();
        $file = $service::getSystemInfo();

        $response = $this->Response();
        $response->headers->set('content-description', 'File Transfer');
        $response->headers->set('content-type', 'application/octet-stream');
        $response->headers->set('content-disposition', 'attachment; filename=' . static::SYSTEM_INFO_FILE_NAME);
        $response->headers->set('cache-control', 'public', true);
        $response->headers->set('content-length', (string) filesize($file));
        $response->sendHeaders();

        $this->Front()->Plugins()->ViewRenderer()->setNoRender();

        $out = fopen('php://output', 'wb');
        $file = fopen($file, 'rb');

        stream_copy_to_stream($file, $out);
    }

    /**
     * @return DebugController
     */
    protected function getBaseController()
    {
        if ($this->baseController === null) {
            $this->baseController = new DebugController();
        }

        return $this->baseController;
    }
}
