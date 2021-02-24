<?php

use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Packlink\BusinessLogic\ShippingMethod\Interfaces\ShopShippingMethodService;
use Packlink\Controllers\Common\CanInstantiateServices;
use Packlink\Entities\ShippingMethodMap;
use Packlink\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException;
use Packlink\Infrastructure\ORM\RepositoryRegistry;
use Packlink\Infrastructure\ServiceRegister;
use Packlink\Utilities\Response;
use Packlink\Utilities\Translation;
use Shopware\Models\Dispatch\Dispatch;
use Shopware\Models\Dispatch\Repository;

/**
 * Class Shopware_Controllers_Backend_PacklinkShopShippingMethod
 */
class Shopware_Controllers_Backend_PacklinkShopShippingMethod extends Enlight_Controller_Action
{
    use CanInstantiateServices;

    /**
     * @var ShopShippingMethodService
     */
    private $shopShippingMethodService;

    /**
     * Returns a list with actions which should not be validated for CSRF protection
     *
     * @return string[]
     */
    public function getWhitelistedCSRFActions()
    {
        return [
            'count',
            'deactivateShopShippingMethods'
        ];
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
        if ($this->shopShippingMethodService === null) {
            $this->shopShippingMethodService = ServiceRegister::getService(ShopShippingMethodService::CLASS_NAME);
        }

        return $this->shopShippingMethodService;
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
}
