<?php

use Doctrine\DBAL\Query\QueryBuilder;
use Packlink\Utilities\Response;


/**
 * Class Shopware_Controllers_Backend_PacklinkShippingCountriesController
 */
class Shopware_Controllers_Backend_PacklinkShippingCountriesController extends Enlight_Controller_Action
{
    /**
     * Retrieves list of available countries.
     */
    public function getAllAction()
    {
        try {
            $countries = $this->getCountries();

            Response::json($this->formatCountries($countries));
        } catch (Exception $e) {
            Response::json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Fetch current shipping countries.
     *
     * @return array
     *
     * @throws Exception
     */
    protected function getCountries()
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->get('dbal_connection')->createQueryBuilder();

        return $queryBuilder->select('id', 'countryname')
            ->from('s_core_countries')
            ->where('allow_shipping = 1')
            ->execute()
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Filters countries for response.
     *
     * @param $countries
     *
     * @return array
     */
    protected function formatCountries($countries)
    {
        $filteredCountries = array_filter(
            $countries,
            static function ($item) {
                return !empty($item['id']) && !empty($item['countryname']);
            }
        );

        $formattedCountries = array_map(
            static function ($item) {
                return [
                    'value' => $item['id'],
                    'label' => $item['countryname'],
                ];
            },
            $filteredCountries
        );

        return array_values($formattedCountries);
    }
}