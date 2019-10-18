<?php

use Logeecom\Infrastructure\Configuration\Configuration;
use Logeecom\Infrastructure\Logger\Logger;
use Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\Scheduler\Models\DailySchedule;
use Packlink\BusinessLogic\Scheduler\Models\HourlySchedule;
use Packlink\BusinessLogic\Scheduler\Models\Schedule;
use Packlink\BusinessLogic\ShippingMethod\Utility\ShipmentStatus;
use Packlink\BusinessLogic\Tasks\UpdateShipmentDataTask;

Logger::logInfo('Started executing V0.1.0 update script.');

$configuration = ServiceRegister::getService(Configuration::CLASS_NAME);
try {
    $repository = RepositoryRegistry::getRepository(Schedule::getClassName());
} catch (RepositoryNotRegisteredException $e) {
    Logger::logError("V0.1.0 update script failed because: {$e->getMessage()}");

    return false;
}

$schedules = $repository->select();

/** @var Schedule $schedule */
foreach ($schedules as $schedule) {
    $task = $schedule->getTask();

    if ($task->getType() === UpdateShipmentDataTask::getClassName()) {
        $repository->delete($schedule);
    }
}

foreach (array(0, 30) as $minute) {
    $hourlyStatuses = array(
        ShipmentStatus::STATUS_PENDING,
    );

    $shipmentDataHalfHourSchedule = new HourlySchedule(
        new UpdateShipmentDataTask($hourlyStatuses),
        $configuration->getDefaultQueueName()
    );
    $shipmentDataHalfHourSchedule->setMinute($minute);
    $shipmentDataHalfHourSchedule->setNextSchedule();
    $repository->save($shipmentDataHalfHourSchedule);
}

$dailyStatuses = array(
    ShipmentStatus::STATUS_IN_TRANSIT,
    ShipmentStatus::STATUS_READY,
    ShipmentStatus::STATUS_ACCEPTED,
);

$dailyShipmentDataSchedule = new DailySchedule(
    new UpdateShipmentDataTask($dailyStatuses),
    $configuration->getDefaultQueueName()
);

$dailyShipmentDataSchedule->setHour(11);
$dailyShipmentDataSchedule->setNextSchedule();

$repository->save($dailyShipmentDataSchedule);

Logger::logInfo('Update script V0.1.0 has been successfully completed.');

return true;