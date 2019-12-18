<?php

use Logeecom\Infrastructure\Configuration\Configuration;
use Logeecom\Infrastructure\Logger\Logger;
use Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Infrastructure\ServiceRegister;
use Logeecom\Infrastructure\TaskExecution\QueueItem;
use Packlink\BusinessLogic\Scheduler\Models\HourlySchedule;
use Packlink\BusinessLogic\Scheduler\Models\Schedule;
use Packlink\BusinessLogic\Scheduler\ScheduleCheckTask;
use Packlink\BusinessLogic\Tasks\TaskCleanupTask;

Logger::logInfo('Started executing V0.9.0 update script.');

$configuration = ServiceRegister::getService(Configuration::CLASS_NAME);
try {
    $repository = RepositoryRegistry::getRepository(Schedule::getClassName());
} catch (RepositoryNotRegisteredException $e) {
    Logger::logError("V0.9.0 update script failed because: {$e->getMessage()}");

    return false;
}

$schedule = new HourlySchedule(
    new TaskCleanupTask(ScheduleCheckTask::getClassName(), array(QueueItem::COMPLETED), 3600),
    $configuration->getDefaultQueueName()
);

$schedule->setMinute(10);
$schedule->setNextSchedule();
$repository->save($schedule);

Logger::logInfo('Update script V0.9.0 has been successfully completed.');

return true;
