<?php

namespace Packlink\Services\BusinessLogic;

use Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use Logeecom\Infrastructure\ORM\Exceptions\RepositoryClassException;
use Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException;
use Logeecom\Infrastructure\ORM\QueryFilter\Operators;
use Logeecom\Infrastructure\ORM\QueryFilter\QueryFilter;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Infrastructure\ServiceRegister;
use Logeecom\Infrastructure\TaskExecution\QueueItem;
use Packlink\BusinessLogic\Configuration;
use Packlink\BusinessLogic\ShippingMethod\Models\ShippingMethod;
use Packlink\Contracts\Services\BusinessLogic\DebugService as BaseService;
use Shopware\Models\Shop\Shop;
use ZipArchive;

class DebugService implements BaseService
{
    const PHP_INFO_FILE_NAME = 'phpinfo.html';
    const SYSTEM_INFO_FILE_NAME = 'system-info.json';
    const PACKLINK_LOG_FILE_NAME = 'plugin';
    const LOG_FILE_NAME = 'logs.txt';
    const SHOPWARE_LOG_FILE = 'system-logs.txt';
    const USER_INFO_FILE_NAME = 'packlink-user-info.json';
    const QUEUE_INFO_FILE_NAME = 'queue.json';
    const PARCEL_WAREHOUSE_FILE_NAME = 'parcel-warehouse.json';
    const SERVICE_INFO_FILE_NAME = 'services.json';
    // 7 days in seconds
    const CUTOFF = 604800;

    /**
     * Returns path to zip archive that contains current system info.
     *
     * @return string
     */
    public static function getSystemInfo()
    {
        $file = tempnam(sys_get_temp_dir(), 'packlink_system_info');

        $zip = new ZipArchive();
        $zip->open($file, ZipArchive::CREATE);
        $phpInfo = static::getPhpInfo();

        if (false !== $phpInfo) {
            $zip->addFromString(static::PHP_INFO_FILE_NAME, $phpInfo);
        }

        $zip->addFromString(static::SYSTEM_INFO_FILE_NAME, static::getShopwareSystemInfo());
        $zip->addFromString(static::LOG_FILE_NAME, static::getLogs(static::PACKLINK_LOG_FILE_NAME));
        $zip->addFromString(static::SHOPWARE_LOG_FILE, static::getLogs('core'));
        $zip->addFromString(static::USER_INFO_FILE_NAME, static::getUserInfo());
        $zip->addFromString(static::QUEUE_INFO_FILE_NAME, static::getQueueStatus());
        $zip->addFromString(static::PARCEL_WAREHOUSE_FILE_NAME, static::getParcelAndWarehouseInfo());
        $zip->addFromString(static::SERVICE_INFO_FILE_NAME, static::getServicesInfo());

        $zip->close();

        return $file;
    }

    /**
     * Retrieves formatted php info.
     *
     * @return false | string
     */
    protected static function getPhpInfo()
    {
        ob_start();
        phpinfo();

        return ob_get_clean();
    }

    /**
     * Retrieves Shopware system info.
     *
     * @return string
     */
    protected static function getShopwareSystemInfo()
    {
        /** @var Configuration $config */
        $config = ServiceRegister::getService(Configuration::CLASS_NAME);

        $result['Shopware version'] = $config->getECommerceVersion();
        $result['theme'] = static::getShopTheme();
        $result['admin url'] = Shopware()->Front()->Router()->assemble(['module' => 'backend']);
        $result['async process url'] = $config->getAsyncProcessUrl('test');
        $result['plugin version'] = $config->getModuleVersion();

        return json_encode($result, JSON_PRETTY_PRINT);
    }

    /**
     * Retrieves contents of log files.
     *
     * @param $logFileName
     *
     * @return string
     */
    protected static function getLogs($logFileName)
    {
        $dir = Shopware()->DocPath('var/log');
        $files = glob($dir . $logFileName . '*.log');
        $filesWithTimeStamp = [];
        $cutoff = time() - static::CUTOFF;

        foreach ($files as $fileName) {
            $time = filectime($fileName);
            if ($time >= $cutoff) {
                $filesWithTimeStamp[] = [
                    'path' => $fileName,
                    'timestamp' => $time,
                ];
            }
        }

        $result = '';

        if (!empty($filesWithTimeStamp)) {
            array_multisort( array_column($filesWithTimeStamp, 'timestamp'), SORT_ASC, $filesWithTimeStamp );
            foreach ($filesWithTimeStamp as $item) {
                if ($contents = file_get_contents($item['path'])) {
                    $result .= $contents . "\n";
                }
            }
        }

        return $result;
    }

    /**
     * Retrieves shop theme name.
     *
     * @return string
     */
    protected static function getShopTheme()
    {
        $shopRepository = Shopware()->Models()->getRepository(Shop::class);
        $query = $shopRepository->createQueryBuilder('shop');
        $query->select(['template.template'])
            ->innerJoin('shop.template', 'template')
            ->where('shop.active = 1')
            ->andWhere('shop.default = 1');

        $result = $query->getQuery()->getArrayResult();

        return !empty($result[0]) ? $result[0] : '';
    }

    /**
     * Retrieves user info.
     *
     * @return string
     */
    protected static function getUserInfo()
    {
        /** @var Configuration $config */
        $config = ServiceRegister::getService(Configuration::CLASS_NAME);
        /** @noinspection NullPointerExceptionInspection */
        $result = $config->getUserInfo()->toArray();
        $result['api key'] = $config->getAuthorizationToken();

        return json_encode($result, JSON_PRETTY_PRINT);
    }

    /**
     * Retrieves current queue status.
     *
     * @return string
     */
    protected static function getQueueStatus()
    {
        $result = [];

        try {
            $repository = RepositoryRegistry::getQueueItemRepository();

            $query = new QueryFilter();
            $query->where('status', Operators::NOT_EQUALS, QueueItem::COMPLETED);

            $result = $repository->select($query);
        } catch (RepositoryNotRegisteredException $e) {
        } catch (QueryFilterInvalidParamException $e) {
        } catch (RepositoryClassException $e) {
        }

        return static::formatJsonOutput($result);
    }

    /**
     * Retrieves current parcel and warehouse information.
     *
     * @return string
     */
    protected static function getParcelAndWarehouseInfo()
    {
        /** @var Configuration $config */
        $config = ServiceRegister::getService(Configuration::CLASS_NAME);

        $result['Default parcel'] = $config->getDefaultParcel() ?: [];
        $result['Default warehouse'] = $config->getDefaultWarehouse() ?: [];

        return json_encode($result, JSON_PRETTY_PRINT);
    }

    /**
     * Retrieves services information.
     *
     * @return string
     */
    protected static function getServicesInfo()
    {
        $result = array();

        try {
            $repository = RepositoryRegistry::getRepository(ShippingMethod::CLASS_NAME);
            $result = $repository->select();
        } catch (RepositoryNotRegisteredException $e) {
        }

        return static::formatJsonOutput($result);
    }

    /**
     * Formats json output.
     *
     * @param array $items
     *
     * @return string
     */
    protected static function formatJsonOutput(array $items)
    {
        $result = [];
        foreach ($items as $item) {
            $result[] = $item->toArray();
        }

        return json_encode($result, JSON_PRETTY_PRINT);
    }
}