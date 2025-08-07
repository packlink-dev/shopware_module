<?php

namespace Packlink\Services\BusinessLogic;

use Doctrine\DBAL\Connection;
use Packlink\BusinessLogic\Country\WarehouseCountryService as CoreService;

/**
 * Class WarehouseCountryService
 *
 * @package Packlink\Services\BusinessLogic
 */
class WarehouseCountryService extends CoreService
{
    /**
     * @param $associative
     *
     * @return \Packlink\BusinessLogic\Country\Country[]
     *
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoNotRegisteredException
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException
     */
    public function getSupportedCountries($associative = true)
    {
        $countries = $this->getBrandConfigurationService()->get()->warehouseCountries;
        /** @var Connection $connection */
        $connection = Shopware()->Container()->get('dbal_connection');
        $sql = "SELECT id, countryiso FROM s_core_countries WHERE active = 1";
        $activeCountries = $connection->fetchAllAssociative($sql);
        $intersectedCountries = [];

        foreach ($activeCountries as $country) {
            if (array_key_exists($country['countryiso'], $countries)) {
                $intersectedCountries[] = $countries[$country['countryiso']];
            }
        }

        $result = $this->formatCountries($intersectedCountries);

        return $associative ? $result : array_values($result);
    }
}
