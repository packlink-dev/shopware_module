<?php

namespace Packlink\Services\BusinessLogic;

use Packlink\Infrastructure\Logger\Logger;
use Packlink\BusinessLogic\Http\DTO\SystemInfo;
use Packlink\BusinessLogic\SystemInformation\SystemInfoService as SystemInfoInterface;
use Shopware\Models\Shop\Repository;

/**
 * Class SystemInfoService
 *
 * @package Packlink\Services\BusinessLogic
 */
class SystemInfoService implements SystemInfoInterface
{
    /**
     * Returns system information.
     *
     * @return SystemInfo[]
     */
    public function getSystemDetails()
    {
        $shop = \Packlink\Utilities\Shop::getDefaultShop();
        if ($shop === null) {
            return array();
        }

        return array(
            SystemInfo::fromArray(
                array(
                    'system_id' => (string)$shop->getId(),
                    'system_name' => $shop->getName(),
                    'currencies' => array($shop->getCurrency()->getCurrency()),
                )
            ),
        );
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
        $details = $this->getSystemDetails();

        if (empty($details)) {
            Logger::logError('No system details found!');

            return null;
        }

        $systemInfo = $details[0];
        if ($systemInfo->systemId !== $systemId) {
            Logger::logError( "System with ID $systemId not found!" );

            return null;
        }

        return $systemInfo;
    }
}
