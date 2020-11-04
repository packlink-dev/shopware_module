<?php

use Packlink\Core\Infrastructure\Configuration\Configuration;
use Packlink\Core\Infrastructure\Logger\Logger;
use Packlink\Core\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException;
use Packlink\Core\Infrastructure\ORM\RepositoryRegistry;
use Packlink\Core\Infrastructure\ServiceRegister;
use Packlink\Core\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException;
use Packlink\Core\Infrastructure\TaskExecution\QueueItem;
use Packlink\Core\Infrastructure\TaskExecution\QueueService;
use Packlink\Core\BusinessLogic\Country\CountryService;
use Packlink\Core\BusinessLogic\OrderShipmentDetails\Models\OrderShipmentDetails;
use Packlink\Core\BusinessLogic\Scheduler\Models\HourlySchedule;
use Packlink\Core\BusinessLogic\Scheduler\Models\Schedule;
use Packlink\Core\BusinessLogic\Scheduler\ScheduleCheckTask;
use Packlink\Core\BusinessLogic\ShipmentDraft\Models\OrderSendDraftTaskMap;
use Packlink\Core\BusinessLogic\Tasks\TaskCleanupTask;
use Packlink\Core\BusinessLogic\Tasks\UpdateShippingServicesTask;
use Packlink\Models\BaseEntity;
use Packlink\Models\PacklinkEntity;

Logger::logInfo('Started executing V0.9.0 update script.');

Logger::logInfo('Cleaning up completed schedulers.');

$configuration = ServiceRegister::getService(Configuration::CLASS_NAME);
/** @var QueueService $queueService */
$queueService = ServiceRegister::getService(QueueService::CLASS_NAME);
try {
    $repository = RepositoryRegistry::getRepository(Schedule::getClassName());

    $schedule = new HourlySchedule(
        new TaskCleanupTask(ScheduleCheckTask::getClassName(), array(QueueItem::COMPLETED), 3600),
        $configuration->getDefaultQueueName()
    );

    $schedule->setMinute(10);
    $schedule->setNextSchedule();
    $repository->save($schedule);

    Logger::logInfo('Scheduler cleanup successful.');
    Logger::logInfo('Migrating old order shipment detail entities.');

    $entityManager = Shopware()->Container()->get('models');
    $query = $entityManager->createQueryBuilder();
    $alias = 'p';
    $query->select($alias)
        ->from(PacklinkEntity::class, $alias)
        ->where("$alias.type = 'OrderShipmentDetails'");
    $entities = $query->getQuery()->getResult();

    if (!empty($entities)) {
        $orderShipmentDetailsRepository = RepositoryRegistry::getRepository(OrderShipmentDetails::getClassName());
        $orderSendDraftRepository = RepositoryRegistry::getRepository(OrderSendDraftTaskMap::getClassName());

        /** @var CountryService $countryService */
        $countryService = ServiceRegister::getService(CountryService::CLASS_NAME);

        $userInfo = $configuration->getUserInfo();
        $userDomain = 'com';
        if ($userInfo && $countryService->isBaseCountry($userInfo->country)) {
            $userDomain = strtolower($userInfo->country);
        }

        $baseShipmentUrl = "https://pro.packlink.$userDomain/private/shipments/";

        /** @var BaseEntity $entity */
        foreach ($entities as $entity) {
            $orderShipmentData = json_decode($entity->getData(), true);

            $orderSendDraftTaskMap = new OrderSendDraftTaskMap();
            $orderSendDraftTaskMap->setOrderId((string)$orderShipmentData['orderId']);
            $orderSendDraftTaskMap->setExecutionId($orderShipmentData['taskId']);
            $orderSendDraftRepository->save($orderSendDraftTaskMap);

            unset($orderShipmentData['taskId']);
            $orderShipmentDetails = OrderShipmentDetails::fromArray($orderShipmentData);
            $orderShipmentDetails->setOrderId((string)$orderShipmentData['orderId']);
            $orderShipmentDetails->setShipmentUrl($baseShipmentUrl . $orderShipmentDetails->getReference());
            $orderShipmentDetailsRepository->update($orderShipmentDetails);
        }
    }

    if ($queueService->findLatestByType('UpdateShippingServicesTask') !== null) {
        $queueService->enqueue($configuration->getDefaultQueueName(), new UpdateShippingServicesTask());
    }

    Logger::logInfo('Migration successful');
} catch (RepositoryNotRegisteredException $e) {
} catch (QueueStorageUnavailableException $e) {
    Logger::logError("V0.9.0 update script failed because: {$e->getMessage()}");

    return false;
}

Logger::logInfo('Update script V0.9.0 has been successfully completed.');

return true;
