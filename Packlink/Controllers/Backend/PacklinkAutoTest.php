<?php

use Packlink\Infrastructure\AutoTest\AutoTestLogger;
use Packlink\Infrastructure\AutoTest\AutoTestService;
use Packlink\Infrastructure\Exceptions\StorageNotAccessibleException;
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
		$version = Shopware()->Config()->version;
		$backendSession = version_compare($version, '5.7.0', '<')? 'BackendSession' : 'backendsession';

		$this->View()->assign(
			[
				'csrfToken' => $this->container->get($backendSession)->offsetGet('X-CSRF-Token'),
			]
		);
	}

	/**
	 * Handles check auto test status action.
	 *
	 * @throws \Packlink\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
	 * @throws \Packlink\Infrastructure\ORM\Exceptions\RepositoryClassException
	 * @throws \Packlink\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
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

        $this->View()->assign(
            'response',
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
	 * @throws \Packlink\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
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

        $this->View()->assign('response', $result);
	}

	/**
	 * Retrieves auto-test logs.
	 *
	 * @throws \Packlink\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
	 */
	public function logsAction()
	{
		$data = json_encode(AutoTestLogger::getInstance()->getLogsArray(), JSON_PRETTY_PRINT);

        $response = $this->Response();
        $response->headers->set('content-description', 'File Transfer');
        $response->headers->set('content-type', 'application/octet-stream');
        $response->headers->set('content-disposition', 'attachment; filename=auto-test-logs.json');
        $response->headers->set('cache-control', 'public', true);
        $response->headers->set('content-length', (string) strlen($data));
        $response->sendHeaders();

        $this->Front()->Plugins()->ViewRenderer()->setNoRender();

        $out = fopen('php://output', 'wb');

        fwrite($out, $data);
        fclose($out);
	}
}