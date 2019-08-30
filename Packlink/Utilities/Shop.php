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
}