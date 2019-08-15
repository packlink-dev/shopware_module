<?php

use Packlink\BusinessLogic\Http\DTO\ParcelInfo;
use Packlink\Controllers\Common\CanInstantiateServices;
use Packlink\Utilities\Request;
use Packlink\Utilities\Response;
use Packlink\Utilities\Translation;
use Shopware\Components\CSRFWhitelistAware;

class Shopware_Controllers_Backend_PacklinkDefaultParcel extends Enlight_Controller_Action implements CSRFWhitelistAware
{
    use CanInstantiateServices;
    /** @var array */
    protected $fields = [
        'weight',
        'width',
        'height',
        'length',
    ];

    /**
     * Returns a list with actions which should not be validated for CSRF protection.
     *
     * @return string[]
     */
    public function getWhitelistedCSRFActions()
    {
        return ['index', 'update'];
    }

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

    protected function validate(array $data)
    {
        $result = [];

        foreach ($this->fields as $field) {
            if (empty($data[$field])) {
                $result[$field] = Translation::get('error/required');
            } else {
                if (!is_numeric($data[$field]) || $data[$field] <= 0) {
                    $result[$field] = Translation::get('error/number');
                }
            }
        }

        return $result;
    }
}