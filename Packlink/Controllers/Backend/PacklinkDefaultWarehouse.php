<?php

use Logeecom\Infrastructure\Logger\Logger;
use Packlink\BusinessLogic\Http\DTO\Warehouse;
use Packlink\Controllers\Common\CanInstantiateServices;
use Packlink\Utilities\Request;
use Packlink\Utilities\Response;
use Packlink\Utilities\Translation;

class Shopware_Controllers_Backend_PacklinkDefaultWarehouse extends Enlight_Controller_Action
{
    use CanInstantiateServices;
    /** @var array */
    protected static $fields = [
        'alias',
        'name',
        'surname',
        'country',
        'postal_code',
        'address',
        'phone',
        'email',
    ];

    /**
     * Retrieves default warehouse.
     */
    public function indexAction()
    {
        $warehouse = $this->getConfigService()->getDefaultWarehouse();
        if (!$warehouse) {
            $userInfo = $this->configService->getUserInfo();
            /** @noinspection NullPointerExceptionInspection */
            $warehouse = Warehouse::fromArray(array('country' => $userInfo->country));
        }

        Response::json($warehouse->toArray());
    }

    /**
     * Updates default warehouse.
     */
    public function updateAction()
    {
        $data = Request::getPostData();
        $validationResult = $this->validate($data);
        if (!empty($validationResult)) {
            Response::json($validationResult, 400);
        }

        $data['default'] = true;
        $this->getConfigService()->setDefaultWarehouse(Warehouse::fromArray($data));

        Response::json($data);
    }

    /**
     * Performs location search.
     */
    public function searchAction()
    {
        $input = Request::getPostData();
        if (empty($input['query'])) {
            Response::json();
        }

        $country = $this->getConfigService()->getUserInfo()->country;

        try {
            $result = $this->getLocationService()->searchLocations($country, $input['query']);
        } catch (Exception $e) {
            $result = [];

            Logger::logError("Location search failed because: [{$e->getMessage()}]");
        }

        $arrayResult = [];
        foreach ($result as $item) {
            $arrayResult[] = $item->toArray();
        }

        Response::json($arrayResult);
    }

    /**
     * Validates default warehouse.
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
            }
        }

        if (!empty($data['country']) && !empty($data['postal_code'])) {
            try {
                $postalCodes = $this->getProxy()->getPostalCodes($data['country'], $data['postal_code']);
                if (empty($postalCodes)) {
                    $result['postal_code'] = Translation::get('error/postalcode');
                }
            } catch (Exception $e) {
                $result['postal_code'] = Translation::get('error/postalcode');
            }
        }

        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $result['email'] = Translation::get('error/email');
        }

        if (!empty($data['phone'])) {
            $regex = '/^(\+|\/|\.|-|\(|\)|\d)+$/m';
            $phoneError = !preg_match($regex, $data['phone']);

            $digits = '/\d/m';
            $match = preg_match_all($digits, $data['phone']);
            $phoneError |= $match === false || $match < 3;

            if ($phoneError) {
                $result['phone'] = Translation::get('error/phone');
            }
        }

        return $result;
    }
}