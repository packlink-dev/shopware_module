<?php

namespace Packlink\Services\Infrastructure;

use Packlink\Core\Infrastructure\Configuration\Configuration;
use Packlink\Core\Infrastructure\Logger\Interfaces\ShopLoggerAdapter;
use Packlink\Core\Infrastructure\Logger\LogData;
use Packlink\Core\Infrastructure\Logger\Logger;
use Packlink\Core\Infrastructure\ServiceRegister;
use Packlink\Core\Infrastructure\Singleton;

class LoggerService extends Singleton implements ShopLoggerAdapter
{
    /**
     * Singleton instance of this class.
     *
     * @var self
     */
    protected static $instance;
    /**
     * Log level names for corresponding log level codes.
     *
     * @var array
     */
    protected static $logLevelName = array(
        Logger::ERROR => 'ERROR',
        Logger::WARNING => 'WARNING',
        Logger::INFO => 'INFO',
        Logger::DEBUG => 'DEBUG',
    );
    /**
     * @var \Shopware\Components\Logger
     */
    protected $logger;

    /**
     * LoggerService constructor.
     *
     * @throws \Exception
     */
    protected function __construct()
    {
        parent::__construct();

        if (Shopware()->Container()->has('packlink.logger')) {
            $this->logger = Shopware()->Container()->get('packlink.logger');
        } else {
            $this->logger = Shopware()->Container()->get('pluginlogger');
        }
    }

    /**
     * Log message in system
     *
     * @param LogData $data
     */
    public function logMessage(LogData $data)
    {
        /** @var Configuration $configService */
        $configService = ServiceRegister::getService(Configuration::CLASS_NAME);
        $minLogLevel = $configService->getMinLogLevel();
        $logLevel = $data->getLogLevel();

        if (($logLevel > $minLogLevel) && !$configService->isDebugModeEnabled()) {
            return;
        }

        $message = 'PACKLINK LOG:' . ' | '
            . 'Date: ' . date('d/m/Y') . ' | '
            . 'Time: ' . date('H:i:s') . ' | '
            . 'Log level: ' . self::$logLevelName[$logLevel] . ' | '
            . 'Message: ' . $data->getMessage();
        $context = $data->getContext();
        if (!empty($context)) {
            $contextData = array();
            foreach ($context as $item) {
                $contextData[$item->getName()] = print_r($item->getValue(), true);
            }

            $message .= ' | ' . 'Context data: [' . json_encode($contextData) . ']';
        }

        $message .= "\n";

        switch ($logLevel) {
            case Logger::ERROR:
                $this->logger->error($message);
                break;
            case Logger::WARNING:
                $this->logger->warning($message);
                break;
            case Logger::INFO:
                $this->logger->info($message);
                break;
            case Logger::DEBUG:
                $this->logger->debug($message);
        }
    }
}