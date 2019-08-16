<?php

use Packlink\Utilities\Response;
use Packlink\Utilities\Translation;
use Shopware\Components\CSRFWhitelistAware;
use Shopware\Models\Dispatch\Dispatch;

class Shopware_Controllers_Backend_PacklinkShopShippingMethod extends Enlight_Controller_Action implements CSRFWhitelistAware
{
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
     */
    public function countAction()
    {
        $count = (int)$this->getDispatchRepository()->createQueryBuilder('d')
            ->select('count(d.id)')
            ->where('d.active=1')->getQuery()->getSingleScalarResult();

        Response::json(['count' => $count]);
    }

    /**
     * Deactivates shop shipping methods.
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function deactivateAction()
    {
        $active = $this->getDispatchRepository()->createQueryBuilder('d')
            ->select('d')
            ->where('d.active=1')->getQuery()->getResult();

        $manager = Shopware()->Models();

        /** @var Dispatch $dispatch */
        foreach ($active as $dispatch) {
            $dispatch->setActive(false);
            $manager->persist($dispatch);
        }

        $manager->flush();

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
}