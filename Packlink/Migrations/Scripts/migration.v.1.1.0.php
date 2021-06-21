<?php

use Doctrine\DBAL\Connection;
use Packlink\BusinessLogic\ShippingMethod\Interfaces\ShopShippingMethodService;
use Packlink\BusinessLogic\ShippingMethod\Models\ShippingMethod;
use Packlink\BusinessLogic\ShippingMethod\Models\ShippingPricePolicy;
use Packlink\Infrastructure\Logger\Logger;
use Packlink\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException;
use Packlink\Infrastructure\ORM\RepositoryRegistry;
use Packlink\Infrastructure\ServiceRegister;

/**
 * Updates shipping methods.
 *
 * @throws RepositoryNotRegisteredException
 */
function updateShippingMethods()
{
    $repository = RepositoryRegistry::getRepository(ShippingMethod::CLASS_NAME);
    /** @var ShopShippingMethodService $shopShippingMethodService */
    $shopShippingMethodService = ServiceRegister::getService(ShopShippingMethodService::CLASS_NAME);
    /** @var Connection $connection */
    $connection = Shopware()->Container()->get('dbal_connection');

    $queryBuilder = $connection->createQueryBuilder();

    $select = $queryBuilder->select('*')
        ->from('packlink_entity')
        ->where("type = 'ShippingService'");

    $entities = $connection->fetchAll($select);

    foreach ($entities as $entity) {
        $data = json_decode($entity['data'], true);
        $data['pricingPolicies'] = getTransformedPricingPolicies($data);
        $data['logoUrl'] = getLogoUrl($data);

        if ($data['id'] === null) {
            $data['id'] = $entity['id'];
        }

        $shippingMethod = ShippingMethod::fromArray($data);
        $repository->update($shippingMethod);

        if ($shippingMethod->isActivated()) {
            $shopShippingMethodService->update($shippingMethod);
        }
    }
}

/**
 * Returns transformed pricing policies for a given shipping method.
 *
 * @param array $method
 *
 * @return array
 */
function getTransformedPricingPolicies(array $method)
{
    $result = [];

    switch ($method['pricingPolicy']) {
        case 1:
            // Packlink prices.
            break;
        case 2:
            // Percent prices.
            $pricingPolicy = new ShippingPricePolicy();
            $pricingPolicy->rangeType = ShippingPricePolicy::RANGE_PRICE_AND_WEIGHT;
            $pricingPolicy->fromPrice = 0;
            $pricingPolicy->fromWeight = 0;
            $pricingPolicy->pricingPolicy = ShippingPricePolicy::POLICY_PACKLINK_ADJUST;
            $pricingPolicy->increase = $method['percentPricePolicy']['increase'];
            $pricingPolicy->changePercent = $method['percentPricePolicy']['amount'];
            $result[] = $pricingPolicy->toArray();
            break;
        case 3:
            // Fixed price by weight.
            foreach ($method['fixedPriceByWeightPolicy'] as $policy) {
                $pricingPolicy = new ShippingPricePolicy();
                $pricingPolicy->rangeType = ShippingPricePolicy::RANGE_WEIGHT;
                $pricingPolicy->fromWeight = $policy['from'];
                $pricingPolicy->toWeight = !empty($policy['to']) ? $policy['to'] : null;
                $pricingPolicy->pricingPolicy = ShippingPricePolicy::POLICY_FIXED_PRICE;
                $pricingPolicy->fixedPrice = $policy['amount'];
                $result[] = $pricingPolicy->toArray();
            }
            break;
        case 4:
            // Fixed price by price.
            foreach ($method['fixedPriceByValuePolicy'] as $policy) {
                $pricingPolicy = new ShippingPricePolicy();
                $pricingPolicy->rangeType = ShippingPricePolicy::RANGE_PRICE;
                $pricingPolicy->fromPrice = $policy['from'];
                $pricingPolicy->toPrice = !empty($policy['to']) ? $policy['to'] : null;
                $pricingPolicy->pricingPolicy = ShippingPricePolicy::POLICY_FIXED_PRICE;
                $pricingPolicy->fixedPrice = $policy['amount'];
                $result[] = $pricingPolicy->toArray();
            }
            break;
    }

    return $result;
}

/**
 * Returns updated carrier logo file path for the given shipping method.
 *
 * @param array $method
 *
 * @return string
 */
function getLogoUrl(array $method)
{
    if (!$method['logoUrl']) {
        /** @var ShopShippingMethodService $shopShippingMethodService */
        $shopShippingMethodService = ServiceRegister::getService(ShopShippingMethodService::CLASS_NAME);
        return $shopShippingMethodService->getCarrierLogoFilePath($method['carrierName']);
    }

    if (strpos($method['logoUrl'], '/images/carriers/') === false) {
        return $method['logoUrl'];
    }

    return str_replace('/images/carriers/', '/packlink/images/carriers/', $method['logoUrl']);
}

/**
 * Converts parcel properties from strings to numbers.
 */
function convertParcelProperties()
{
    /** @var Connection $connection */
    $connection = Shopware()->Container()->get('dbal_connection');
    $queryBuilder = $connection->createQueryBuilder();
    $select = $queryBuilder->select('*')
        ->from('packlink_entity')
        ->where("index_1 = 'defaultParcel'");

    $entities = $connection->fetchAll($select);

    foreach ($entities as $entity) {
        if (empty($entity['data'])) {
            continue;
        }

        $parcel = json_decode($entity['data'], true);

        if (!empty($parcel['value']['weight'])) {
            $weight = (float)$parcel['value']['weight'];
            $parcel['value']['weight'] = !empty($weight) ? $weight : 1;
        }

        foreach (['length', 'height', 'width'] as $field) {
            if (!empty($parcel['value'][$field])) {
                $fieldValue = (int)$parcel['value'][$field];
                $parcel['value'][$field] = !empty($fieldValue) ? $fieldValue : 10;
            }
        }

        if (!empty($entity['id'])) {
            $connection->update('packlink_entity', ['data' => json_encode($parcel)], ['id' => $entity['id']]);
        }
    }
}

Logger::logInfo('Started executing V1.1.1 update script.');

try {
    updateShippingMethods();
    convertParcelProperties();
} catch (RepositoryNotRegisteredException $e) {
    Logger::logError("V1.1.0 update script failed because: {$e->getMessage()}");

    return false;
}

Logger::logInfo('Update script V1.1.0 has been successfully completed.');

return true;