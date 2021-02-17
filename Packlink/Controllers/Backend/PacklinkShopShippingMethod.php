<?php

use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Packlink\BusinessLogic\Controllers\DTO\ShippingMethodConfiguration;
use Packlink\BusinessLogic\Controllers\ShippingMethodController;
use Packlink\BusinessLogic\Controllers\UpdateShippingServicesTaskStatusController;
use Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException;
use Packlink\BusinessLogic\ShippingMethod\Interfaces\ShopShippingMethodService;
use Packlink\BusinessLogic\Tax\TaxClass;
use Packlink\Controllers\Common\CanInstantiateServices;
use Packlink\Entities\ShippingMethodMap;
use Packlink\Infrastructure\Exceptions\BaseException;
use Packlink\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException;
use Packlink\Infrastructure\ORM\RepositoryRegistry;
use Packlink\Infrastructure\ServiceRegister;
use Packlink\Infrastructure\TaskExecution\QueueItem;
use Packlink\Utilities\Request;
use Packlink\Utilities\Response;
use Packlink\Utilities\Translation;
use Shopware\Models\Dispatch\Dispatch;
use Shopware\Models\Dispatch\Repository;
use Shopware\Models\Tax\Tax;

/**
 * Class Shopware_Controllers_Backend_PacklinkShopShippingMethod
 */
class Shopware_Controllers_Backend_PacklinkShopShippingMethod extends Enlight_Controller_Action
{
    use CanInstantiateServices;

    /**
     * Returns a list with actions which should not be validated for CSRF protection
     *
     * @return string[]
     */
    public function getWhitelistedCSRFActions()
    {
        return [
            'getAll',
            'getActive',
            'getInactive',
            'getShippingMethod',
            'getTaskStatus',
            'activate',
            'deactivate',
            'save',
            'getTaxClasses',
            'count',
            'deactivateShopShippingMethods'
        ];
    }

    /**
     * Returns all available shipping methods.
     */
    public function getAllAction()
    {
        $shippingMethods = $this->getBaseController()->getAll();

        Response::dtoEntitiesResponse($shippingMethods);
    }

    /**
     * Returns active shipping methods.
     */
    public function getActiveAction()
    {
        $shippingMethods = $this->getBaseController()->getActive();

        Response::dtoEntitiesResponse($shippingMethods);
    }

    /**
     * Returns inactive shipping methods.
     */
    public function getInactiveAction()
    {
        $shippingMethods = $this->getBaseController()->getInactive();

        Response::dtoEntitiesResponse($shippingMethods);
    }

    /**
     * Returns a single shipping method identified by the provided ID.
     */
    public function getShippingMethodAction()
    {
        $data = Request::getPostData();

        if (empty($data['id'])) {
            Response::json(['success' => false], 400);
        }

        $shippingMethod = $this->getBaseController()->getShippingMethod($data['id']);

        if ($shippingMethod === null) {
            Response::json(['success' => false], 401);
        }

        Response::json($shippingMethod->toArray());
    }

    /**
     * Gets the status of the task for updating shipping services.
     */
    public function getTaskStatusAction()
    {
        $status = QueueItem::FAILED;
        try {
            $controller = new UpdateShippingServicesTaskStatusController();
            $status = $controller->getLastTaskStatus();
        } catch (BaseException $e) {
        }

        Response::json(['status' => $status]);
    }

    /**
     * Activates shipping method.
     */
    public function activateAction()
    {
        $data = Request::getPostData();

        if (!$data['id'] || !$this->getBaseController()->activate((int)$data['id'])) {
            Response::json(['success' => false, 'message' => Translation::get('error/shippingmethodactivate')], 400);
        }

        Response::json(['success' => true, 'message' => Translation::get('success/shippingmethodactivate')]);
    }

    /**
     * Deactivates shipping method.
     */
    public function deactivateAction()
    {
        $data = Request::getPostData();

        if (!$data['id'] || !$this->getBaseController()->deactivate((int)$data['id'])) {
            Response::json(['success' => false, 'message' => Translation::get('error/shippingmethoddeactivate')], 400);
        }

        Response::json(['success' => true, 'message' => Translation::get('success/shippingmethoddeactivate')]);
    }

    /**
     * Saves shipping method.
     */
    public function saveAction()
    {
        try {
            $configuration = $this->getShippingMethodConfiguration();
        } catch (FrontDtoValidationException $e) {
            Response::validationErrorsResponse($e->getValidationErrors());
        }

        $model = $this->getBaseController()->save($configuration);

        if ($model === null) {
            Response::json(['message' => Translation::get('error/shippingmethodsave')], 400);
        }

        if ($model->activated) {
            Response::json($model->toArray());
        }

        if (!$model->id || !$this->getBaseController()->activate((int)$model->id)) {
            Response::json(['message' => Translation::get('error/shippingmethodactivate')], 400);
        }

        Response::json($model->toArray());
    }

    /**
     * Retrieves available taxes.
     */
    public function getTaxClassesAction()
    {
        $result = [];

        try {
            $result[] = TaxClass::fromArray(
                [
                    'value' => 0,
                    'label' => Translation::get('configuration/defaulttax'),
                ]
            );

            $availableTaxes = $this->getTaxRepository()->queryAll()->execute();

            /** @var Tax $tax */
            foreach ($availableTaxes as $tax) {
                $result[] = TaxClass::fromArray([
                    'value' => $tax->getId(),
                    'label' => $tax->getName(),
                ]);
            }

            Response::dtoEntitiesResponse($result);
        } catch (FrontDtoValidationException $e) {
            Response::validationErrorsResponse($e->getValidationErrors());
        }
    }

    /**
     * Retrieves count of active shipping methods.
     *
     * @throws NoResultException
     * @throws NonUniqueResultException
     * @throws RepositoryNotRegisteredException
     */
    public function countAction()
    {
        $query = $this->getDispatchRepository()->createQueryBuilder('d')
            ->select('count(d.id)')
            ->where('d.active=1');

        if ($packlinkShippingMethods = $this->getPacklinkShippingMethods()) {
            $query->andWhere('d.id not in (' . implode(',', $packlinkShippingMethods) . ')');
        }

        $count = (int)$query->getQuery()->getSingleScalarResult();

        Response::json(['count' => $count]);
    }

    /**
     * Deactivates shop shipping methods.
     */
    public function deactivateShopShippingMethodsAction()
    {
        $this->getShopShippingMethodService()->disableShopServices();

        Response::json(['message' => Translation::get('success/disableshopshippingmethod')]);
    }

    /**
     * Retrieves dispatch repository.
     *
     * @return Repository
     */
    protected function getDispatchRepository()
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return Shopware()->Models()->getRepository(Dispatch::class);
    }

    /**
     * @return ShopShippingMethodService
     */
    protected function getShopShippingMethodService()
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService(ShopShippingMethodService::CLASS_NAME);
    }

    /**
     * @return ShippingMethodController
     */
    public function getBaseController()
    {
        return new ShippingMethodController();
    }

    /**
     * Retrieves packlink shipping methods.
     *
     * @return array
     *
     * @throws RepositoryNotRegisteredException
     */
    protected function getPacklinkShippingMethods()
    {
        $repository = RepositoryRegistry::getRepository(ShippingMethodMap::getClassName());
        $maps = $repository->select();
        $methodIds = array_map(
            static function (ShippingMethodMap $item) {
                return $item->shopwareCarrierId;
            },
            $maps
        );

        $backupId = $this->getConfigService()->getBackupCarrierId();

        if ($backupId !== null) {
            $methodIds[] = $backupId;
        }

        return $methodIds;
    }

    /**
     * Returns shipping method configuration.
     *
     * @return ShippingMethodConfiguration
     *
     * @throws FrontDtoValidationException
     */
    protected function getShippingMethodConfiguration()
    {
        $data = Request::getPostData();

        $data['taxClass'] = (int)$data['taxClass'];

        return ShippingMethodConfiguration::fromArray($data);
    }

    /**
     * Retrieves tax repository.
     *
     * @return \Shopware\Models\Tax\Repository
     */
    protected function getTaxRepository()
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return Shopware()->Models()->getRepository(Tax::class);
    }
}