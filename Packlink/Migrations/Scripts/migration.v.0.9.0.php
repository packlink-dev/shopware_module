<?php

use Packlink\Infrastructure\Configuration\Configuration;
use Packlink\Infrastructure\Logger\Logger;
use Packlink\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException;
use Packlink\Infrastructure\ORM\RepositoryRegistry;
use Packlink\Infrastructure\ServiceRegister;
use Packlink\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException;
use Packlink\Infrastructure\TaskExecution\QueueItem;
use Packlink\Infrastructure\TaskExecution\QueueService;
use Packlink\BusinessLogic\OrderShipmentDetails\Models\OrderShipmentDetails;
use Packlink\BusinessLogic\Scheduler\Models\HourlySchedule;
use Packlink\BusinessLogic\Scheduler\Models\Schedule;
use Packlink\BusinessLogic\Scheduler\ScheduleCheckTask;
use Packlink\BusinessLogic\ShipmentDraft\Models\OrderSendDraftTaskMap;
use Packlink\BusinessLogic\Tasks\TaskCleanupTask;
use Packlink\BusinessLogic\Tasks\UpdateShippingServicesTask;
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

        $userInfo = $configuration->getUserInfo();
        $userDomain = 'com';
        if ($userInfo && in_array($userInfo->country, array('ES', 'DE', 'FR', 'IT'))) {
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
