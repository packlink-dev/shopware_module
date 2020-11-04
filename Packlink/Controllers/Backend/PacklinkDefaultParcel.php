<?php

use Packlink\Core\BusinessLogic\Http\DTO\ParcelInfo;
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
            Response::json();
        }

        Response::json($parcel->toArray());
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
            Response::json($parcelInfo->toArray());
        } catch (\Packlink\Core\BusinessLogic\DTO\Exceptions\FrontDtoValidationException $e) {
            Response::validationErrorsResponse($e->getValidationErrors());
        }
    }
}
