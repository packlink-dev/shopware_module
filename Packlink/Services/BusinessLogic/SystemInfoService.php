<?php

namespace Packlink\Services\BusinessLogic;

use Packlink\BusinessLogic\Http\DTO\SystemInfo;
use Packlink\BusinessLogic\SystemInformation\SystemInfoService as SystemInfoInterface;
use Shopware\Models\Shop\Shop;
use Shopware\Models\Shop\Repository;

/**
 * Class SystemInfoService
 *
 * @package Packlink\Services\BusinessLogic
 */
class SystemInfoService implements SystemInfoInterface
{
    /**
     * Shop repository.
     *
     * @var Repository
     */
    private $repository;

    /**
     * Returns system information.
     *
     * @return SystemInfo[]
     */
    public function getSystemDetails()
    {
        $shops = $this->getShops();
        $systemDetails = array();

        foreach ($shops as $shop) {
            $systemDetails[] = SystemInfo::fromArray([
                'system_id' => (string)$shop->getId(),
                'system_name' => $shop->getName(),
                'currencies' => array($shop->getCurrency()->getCurrency())
            ]);
        }

        return $systemDetails;
    }

    /**
     * Returns system information for a particular system, identified by the system ID.
     *
     * @param string $systemId
     *
     * @return SystemInfo|null
     */
    public function getSystemInfo($systemId)
    {
        $shop = $this->getShop($systemId);
        if (empty($shop)) {
            return null;
        }

        return SystemInfo::fromArray([
            'system_id' => $systemId,
            'system_name' => $shop->getName(),
            'currencies' => array($shop->getCurrency()->getCurrency())
        ]);
    }

    /**
     * Returns all shops.
     *
     * @return Shop[]
     */
    protected function getShops()
    {
        return $this->getRepository()->getShopsWithThemes()->getResult();
    }

    /**
     * Returns a shop instance identified by its ID.
     *
     * @param string $systemId
     *
     * @return \Shopware\Models\Shop\DetachedShop|null
     */
    protected function getShop($systemId)
    {
        return $this->getRepository()->getActiveById($systemId);
    }

    /**
     * Returns shop repository.
     *
     * @return Repository
     */
    private function getRepository()
    {
        if ($this->repository === null) {
            $this->repository = Shopware()->Container()->get('models')->getRepository(Shop::class);
        }

        return $this->repository;
    }
}
