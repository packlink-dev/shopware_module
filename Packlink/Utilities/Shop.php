<?php

namespace Packlink\Utilities;

use Shopware\Models\Shop\Currency;

class Shop
{
    /**
     * Retrieves default shop.
     *
     * @return \Shopware\Models\Shop\Shop
     */
    public static function getDefaultShop()
    {
        /** @var \Shopware\Models\Shop\Repository $repository */
        $repository = Shopware()->Models()->getRepository(\Shopware\Models\Shop\Shop::class);

        return $repository->getDefault();
    }

    /**
     * Returns default system currency.
     *
     * @return string
     */
    public static function getDefaultCurrency()
    {
        $repository = Shopware()->Models()->getRepository(Currency::class);

        /** @var Currency $currency */
        $currency = $repository->findOneBy(['default' => true]);

        return $currency !== null ? $currency->getCurrency() : '';
    }
}