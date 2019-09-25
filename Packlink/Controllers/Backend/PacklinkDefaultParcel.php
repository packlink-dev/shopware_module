<?php

use Packlink\BusinessLogic\Http\DTO\ParcelInfo;
use Packlink\Controllers\Common\CanInstantiateServices;
use Packlink\Utilities\Request;
use Packlink\Utilities\Response;
use Packlink\Utilities\Translation;

class Shopware_Controllers_Backend_PacklinkDefaultParcel extends Enlight_Controller_Action
{
    use CanInstantiateServices;
    /** @var array */
    protected static $fields = [
        'weight',
        'width',
        'height',
        'length',
    ];

    /**
     * Retrieves default parcel.
     */
    public function indexAction()
    {
        $parcel = $this->getConfigService()->getDefaultParcel();
        $data = $parcel ? $parcel->toArray() : [];

        Response::json($data);
    }

    /**
     * Updates default parcel.
     */
    public function updateAction()
    {
        $data = Request::getPostData();
        $validationResult = $this->validate($data);

        if (!empty($validationResult)) {
            Response::json($validationResult, 400);
        }

        $data['default'] = true;
        $this->getConfigService()->setDefaultParcel(ParcelInfo::fromArray($data));

        Response::json($data);
    }

    /**
     * Validates default parcel.
     *
     * @param array $data
     *
     * @return array
     */
    protected function validate(array $data)
    {
        $result = [];

        foreach (self::$fields as $field) {
            if (empty($data[$field])) {
                $result[$field] = Translation::get('error/required');
            } else if (!is_numeric($data[$field]) || $data[$field] <= 0) {
                $result[$field] = Translation::get('error/number');
            }
        }

        return $result;
    }
}