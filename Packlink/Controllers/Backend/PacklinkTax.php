<?php

use Packlink\Core\BusinessLogic\Tax\TaxClass;
use Packlink\Utilities\Response;
use Packlink\Utilities\Translation;
use Shopware\Models\Tax\Tax;

class Shopware_Controllers_Backend_PacklinkTax extends Enlight_Controller_Action
{
    /**
     * Retrieves available taxes.
     */
    public function listAction()
    {
        $result = [];

        $result[] = TaxClass::fromArray(
            [
                'value' => 0,
                'label' => Translation::get('configuration/defaulttax'),
            ]
        );

        $availableTaxes = $this->getTaxRepository()->queryAll()->execute();

        /** @var Tax $tax */
        foreach ($availableTaxes as $tax) {
            $result[] = TaxClass::fromArray([
                'value' => $tax->getId(),
                'label' => $tax->getName(),
            ]);
        }

        Response::dtoEntitiesResponse($result);
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
