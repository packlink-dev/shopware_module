<?php

use Packlink\Infrastructure\ORM\RepositoryRegistry;
use Packlink\BusinessLogic\Controllers\AnalyticsController;
use Packlink\Controllers\Common\CanInstantiateServices;
use Packlink\Entities\ShippingMethodMap;
use Packlink\Utilities\Response;
use Packlink\Utilities\Translation;
use Shopware\Models\Dispatch\Dispatch;

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
        return ['count', 'deactivate'];
    }

    /**
     * Retrieves count of active shipping methods.
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Packlink\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
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
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Packlink\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function deactivateAction()
    {
        $query = $this->getDispatchRepository()->createQueryBuilder('d')
            ->select('d')
            ->where('d.active=1');

        if ($packlinkShippingMethods = $this->getPacklinkShippingMethods()) {
            $query->andWhere('d.id not in (' . implode(',', $packlinkShippingMethods) . ')');
        }

        $active = $query->getQuery()->getResult();

        $manager = Shopware()->Models();

        /** @var Dispatch $dispatch */
        foreach ($active as $dispatch) {
            $dispatch->setActive(false);
            $manager->persist($dispatch);
        }

        $manager->flush();
        AnalyticsController::sendOtherServicesDisabledEvent();

        Response::json(['message' => Translation::get('success/disableshopshippingmethod')]);
    }

    /**
     * Retrieves dispatch repository.
     *
     * @return \Shopware\Models\Dispatch\Repository
     */
    protected function getDispatchRepository()
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return Shopware()->Models()->getRepository(Dispatch::class);
    }

    /**
     * Retrieves packlink shipping methods.
     *
     * @return array
     *
     * @throws \Packlink\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    protected function getPacklinkShippingMethods()
    {
        $repository = RepositoryRegistry::getRepository(ShippingMethodMap::getClassName());
        $maps = $repository->select();
        $methodIds = array_map(
            function (ShippingMethodMap $item) {
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