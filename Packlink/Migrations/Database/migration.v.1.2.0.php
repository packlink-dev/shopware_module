<?php

use Packlink\Bootstrap\Bootstrap;
use Packlink\BusinessLogic\Configuration;
use Packlink\BusinessLogic\ShippingMethod\Models\ShippingMethod;
use Packlink\BusinessLogic\ShippingMethod\Models\ShippingPricePolicy;
use Packlink\BusinessLogic\SystemInformation\SystemInfoService;
use Packlink\BusinessLogic\Tasks\UpdateShippingServicesTask;
use Packlink\Infrastructure\ORM\RepositoryRegistry;
use Packlink\Infrastructure\ServiceRegister;
use Packlink\Infrastructure\TaskExecution\QueueService;

/**
 * Updates a shipping method.
 *
 * @param array $method
 * @param \Packlink\BusinessLogic\Http\DTO\SystemInfo[] $systemDetails
 *
 * @return mixed
 * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException
 */
function updateShippingMethod($method, $systemDetails)
{
    $method['currency'] = 'EUR';
    $method['fixedPrices'] = null;
    $method['systemDefaults'] = null;
    $method['pricingPolicies'] = getSystemSpecificPricingPolicies($method, $systemDetails);

    return $method;
}

/**
 * Returns system specific pricing policies.
 *
 * @param array $method
 * @param \Packlink\BusinessLogic\Http\DTO\SystemInfo[] $systemDetails
 *
 * @return array
 *
 * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException
 */
function getSystemSpecificPricingPolicies($method, $systemDetails)
{
    $policies = [];

    if (!empty($method['pricingPolicies'])) {
        foreach ($method['pricingPolicies'] as $policy) {
            foreach ($systemDetails as $systemInfo) {
                $newPolicy = ShippingPricePolicy::fromArray($policy);
                $newPolicy->systemId = $systemInfo->systemId;

                $policies[] = $newPolicy->toArray();
            }
        }
    }

    return $policies;
}

Bootstrap::init();

/** @var \Packlink\Services\BusinessLogic\SystemInfoService $systemInfoService */
$systemInfoService = ServiceRegister::getService(SystemInfoService::CLASS_NAME);
$systemDetails = $systemInfoService->getSystemDetails();

// ***********************************************************************************
// STEP 1. ***************************************************************************
// Read the current shipping methods.                                                *
// ***********************************************************************************
$records = Shopware()->Db()->fetchAll(
    "SELECT * FROM packlink_entity
     WHERE packlink_entity.type = 'ShippingService'"
);

$methods = array_values(
    array_map(
        static function ($record) {
            return json_decode($record['data'], true);
        },
        $records
    )
);

// ***********************************************************************************
// STEP 2. ***************************************************************************
// Transform the shipping methods.                                                   *
// ***********************************************************************************
foreach ($methods as $index => $method) {
    /** @noinspection PhpUnhandledExceptionInspection */
    $methods[$index] = updateShippingMethod($method, $systemDetails);
}

// ***********************************************************************************
// STEP 3. ***************************************************************************
// Instantiate new shipping methods with the transformed data                        *
// ***********************************************************************************
$updatedShippingMethods = array_map(
    static function (array $method) {
        return ShippingMethod::fromArray($method);
    },
    $methods
);

// ***********************************************************************************
// STEP 4. ***************************************************************************
// Save the updated shipping methods.                                                *
// ***********************************************************************************
/** @noinspection PhpUnhandledExceptionInspection */
$repository = RepositoryRegistry::getRepository(ShippingMethod::getClassName());
foreach ($updatedShippingMethods as $method) {
    $repository->update($method);
}

// ***********************************************************************************
// STEP 5. ***************************************************************************
// Enqueue task for updating shipping services.                                      *
// ***********************************************************************************
/** @var \Packlink\Services\BusinessLogic\ConfigurationService $configService */
$configService = ServiceRegister::getService( Configuration::CLASS_NAME );
/** @var QueueService $queueService */
$queueService = ServiceRegister::getService( QueueService::CLASS_NAME );
if ($queueService->findLatestByType('UpdateShippingServicesTask') !== null) {
    /** @noinspection PhpUnhandledExceptionInspection */
    $queueService->enqueue($configService->getDefaultQueueName(), new UpdateShippingServicesTask());
}

return true;
