<?php

use Packlink\Utilities\Response;
use Packlink\Utilities\Translation;
use Shopware\Components\CSRFWhitelistAware;
use Shopware\Models\Tax\Tax;

class Shopware_Controllers_Backend_PacklinkTax extends Enlight_Controller_Action implements CSRFWhitelistAware
{
    /**
     * Returns a list with actions which should not be validated for CSRF protection
     *
     * @return string[]
     */
    public function getWhitelistedCSRFActions()
    {
        return ['list'];
    }

    /**
     * Retrieves available taxes.
     */
    public function listAction()
    {
        $result[] = [
            'value' => 0,
            'label' => Translation::get('configuration/defaulttax'),
        ];

        $availableTaxes = $this->getTaxRepository()->queryAll()->execute();

        /** @var Tax $tax */
        foreach ($availableTaxes as $tax) {
            $result[] = [
                'value' => $tax->getId(),
                'label' => $tax->getName(),
            ];
        }

        Response::json($result);
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