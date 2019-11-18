<?php

use Logeecom\Infrastructure\AutoTest\AutoTestLogger;
use Logeecom\Infrastructure\AutoTest\AutoTestService;
use Logeecom\Infrastructure\Exceptions\StorageNotAccessibleException;
use Packlink\Services\Infrastructure\LoggerService;
use Packlink\Utilities\Response;
use Shopware\Components\CSRFWhitelistAware;

class Shopware_Controllers_Backend_PacklinkAutoTest extends Enlight_Controller_Action implements CSRFWhitelistAware
{
    /**
     * Returns a list with actions which should not be validated for CSRF protection
     *
     * @return string[]
     */
    public function getWhitelistedCSRFActions()
    {
        return ['index'];
    }

    /**
     * Retrieves template.
     *
     * @throws \Exception
     */
    public function indexAction()
    {
        $this->View()->assign(
            [
                'csrfToken' => $this->container->get('BackendSession')->offsetGet('X-CSRF-Token'),
            ]
        );
    }

    /**
     * Handles check auto test status action.
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryClassException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function statusAction()
    {
        $itemId = $this->Request()->get('queueItemId');
        $service = new AutoTestService();
        $status = $service->getAutoTestTaskStatus($itemId);

        if ($status->finished) {
            $service->stopAutoTestMode(
                function () {
                    return LoggerService::getInstance();
                }
            );
        }

        Response::json(
            [
                'finished' => $status->finished,
                'error' => $status->error,
                'logs' => AutoTestLogger::getInstance()->getLogsArray(),
            ]
        );
    }

    /**
     * Starts auto-test.
     *
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     */
    public function startAction()
    {
        $service = new AutoTestService();

        try {
            $result = [
                'success' => true,
                'itemId' => $service->startAutoTest(),
            ];
        } catch (StorageNotAccessibleException $e) {
            $result = [
                'success' => false,
                'error' => 'Database not accessible.',
            ];
        }

        Response::json($result);
    }

    /**
     * Retrieves auto-test logs.
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function logsAction()
    {
        $data = json_encode(AutoTestLogger::getInstance()->getLogsArray(), JSON_PRETTY_PRINT);

        Response::fileFromString($data, 'auto-test-logs.json');
    }
}