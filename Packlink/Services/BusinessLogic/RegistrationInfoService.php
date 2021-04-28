<?php


namespace Packlink\Services\BusinessLogic;


use Doctrine\DBAL\Connection;
use Packlink\BusinessLogic\Registration\RegistrationInfo;
use Packlink\BusinessLogic\Registration\RegistrationInfoService as BaseService;
use Packlink\Utilities\Shop;

/**
 * Class RegistrationInfoService
 *
 * @package Packlink\Services\BusinessLogic
 */
class RegistrationInfoService implements BaseService
{

    /**
     * Returns registration data from the integration.
     *
     * @return RegistrationInfo
     */
    public function getRegistrationInfoData()
    {
        $source = Shop::getDefaultShop()->getHost();
        $email = $this->getEmail();

        return new RegistrationInfo($email, '', $source);
    }

    /**
     * @return string
     */
    protected function getEmail()
    {
        /** @var Connection $connection */
        $connection = Shopware()->Container()->get('dbal_connection');

        $elementId = $connection->fetchColumn('SELECT id FROM s_core_config_elements WHERE name LIKE "mail"');
        $serializedMail = $connection
            ->fetchColumn(
                'SELECT value FROM s_core_config_values WHERE element_id = :elementId',
                ['elementId' => $elementId]
            );

        return unserialize($serializedMail);
    }
}