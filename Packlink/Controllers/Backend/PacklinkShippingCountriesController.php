<?php

use Doctrine\DBAL\Query\QueryBuilder;

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

            $this->View()->assign(['response' => $this->formatCountries($countries)]);
        } catch (Exception $e) {
            $this->Response()->setStatusCode(400);
            $this->View()->assign('response', ['success' => false, 'message' => $e->getMessage()]);
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

        if (version_compare(Shopware()->Config()->version, '5.6.2', '>')) {
            return $queryBuilder->select('id', 'countryname')
                ->from('s_core_countries')
                ->where('allow_shipping = 1')
                ->execute()
                ->fetchAll(PDO::FETCH_ASSOC);
        }

        return $queryBuilder->select('id', 'countryname')
            ->from('s_core_countries')
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