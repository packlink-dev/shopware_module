<?php

namespace Packlink\Utilities;

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
        $currency = Shopware()->Db()->fetchRow(
            'SELECT currency
            FROM s_core_currencies
            WHERE standard = 1'
        );

        return !empty($currency) ? $currency['currency'] : '';
    }
}