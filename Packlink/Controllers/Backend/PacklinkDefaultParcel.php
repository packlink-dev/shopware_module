<?php

use Packlink\BusinessLogic\Http\DTO\ParcelInfo;
use Packlink\Controllers\Common\CanInstantiateServices;
use Packlink\Utilities\Request;
use Packlink\Utilities\Response;

class Shopware_Controllers_Backend_PacklinkDefaultParcel extends Enlight_Controller_Action
{
    use CanInstantiateServices;

    /**
     * Retrieves default parcel.
     */
    public function indexAction()
    {
        $parcel = $this->getConfigService()->getDefaultParcel();

        if (!$parcel) {
            $this->View()->assign(['response' => '']);

            return;
        }

        $this->View()->assign(['response' => $parcel->toArray()]);
    }

    /**
     * Updates default parcel.
     */
    public function updateAction()
    {
        $data = Request::getPostData();
        $data['default'] = true;

        try {
            $parcelInfo = ParcelInfo::fromArray($data);
            $this->getConfigService()->setDefaultParcel($parcelInfo);
            $this->View()->assign(['response' => $parcelInfo->toArray()]);
        } catch (\Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException $e) {
            $this->View()->assign('response', Response::validationErrorsResponse($e->getValidationErrors()));
        }
    }
}
