<?php

namespace Packlink\Repositories;

use Exception;
use Packlink\Bootstrap\Database;
use Packlink\Infrastructure\ORM\Interfaces\QueueItemRepository as BaseQueueItemRepository;
use Packlink\Infrastructure\ORM\QueryFilter\Operators;
use Packlink\Infrastructure\ORM\QueryFilter\QueryFilter;
use Packlink\Infrastructure\ORM\Utility\IndexHelper;
use Packlink\Infrastructure\TaskExecution\Exceptions\QueueItemSaveException;
use Packlink\Infrastructure\TaskExecution\Interfaces\Priority;
use Packlink\Infrastructure\TaskExecution\QueueItem;

class QueueItemRepository extends BaseRepository implements BaseQueueItemRepository
{
    /**
     * Fully qualified name of this class.
     */
    const THIS_CLASS_NAME = __CLASS__;

    /**
     * Finds list of earliest queued queue items per queue. Following list of criteria for searching must be satisfied:
     *      - Queue must be without already running queue items
     *      - For one queue only one (oldest queued) item should be returned
     *
     * @param int $priority Queue item priority.
     * @param int $limit Result set limit. By default max 10 earliest queue items will be returned
     *
     * @return QueueItem[] Found queue item list
     */
    public function findOldestQueuedItems($priority, $limit = 10)
    {
        if ($priority !== Priority::NORMAL) {
            return [];
        }

        $result = [];

        try {
            $result = $this->getQueueItems($limit);
        } catch (Exception $e) {
            // In case of database exception return empty result set.
        }

        return $result;
    }

    /**
     * Creates or updates given queue item. If queue item id is not set, new queue item will be created otherwise
     * update will be performed.
     *
     * @param QueueItem $queueItem Item to save
     * @param array $additionalWhere List of key/value pairs that must be satisfied upon saving queue item. Key is
     *  queue item property and value is condition value for that property. Example for MySql storage:
     *  $storage->save($queueItem, array('status' => 'queued')) should produce query
     *  UPDATE queue_storage_table SET .... WHERE .... AND status => 'queued'
     *
     * @return int Id of saved queue item
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Packlink\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Packlink\Infrastructure\TaskExecution\Exceptions\QueueItemSaveException if queue item could not be saved
     */
    public function saveWithCondition(QueueItem $queueItem, array $additionalWhere = array())
    {
        if ($queueItem->getId()) {
            $this->updateQueueItem($queueItem, $additionalWhere);

            return $queueItem->getId();
        }

        return $this->save($queueItem);
    }

    /**
     * Retrieves queue items
     *
     * @param $limit
     *
     * @return QueueItem[]
     *
     * @throws \Exception
     */
    protected function getQueueItems($limit)
    {
        $connection = Shopware()->Container()->get('dbal_connection');

        $index = $this->getColumnIndexMap();
        $nameColumn = 'index_' . $index['queueName'];
        $statusColumn = 'index_' . $index['status'];
        $queuedStatus = QueueItem::QUEUED;
        $inProgressStatus = QueueItem::IN_PROGRESS;

        $runningQueuesQuery = "SELECT DISTINCT {$nameColumn} 
                                FROM {$this->getDbName()} as q2 
                                WHERE q2.{$statusColumn}='{$inProgressStatus}'";

        $sql = "SELECT queueTable.id, queueTable.data
                FROM (
                    SELECT t.{$nameColumn}, MIN(id) as id
                    FROM {$this->getDbName()} as t
                    WHERE t.{$statusColumn}='{$queuedStatus}' AND t.{$nameColumn} NOT IN ({$runningQueuesQuery})
                    GROUP BY t.{$nameColumn}
                    LIMIT {$limit}
                ) as queueView
                INNER JOIN {$this->getDbName()} as queueTable
                ON queueView.{$nameColumn}=queueTable.{$nameColumn} AND queueView.id=queueTable.id";

        $rawItems = $connection->fetchAll($sql);

        return $this->inflateQueueItems(!empty($rawItems) ? $rawItems : []);
    }

    /**
     * Updates queue item.
     *
     * @param \Packlink\Infrastructure\TaskExecution\QueueItem $queueItem
     * @param array $additionalWhere
     *
     *
     * @throws \Packlink\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Packlink\Infrastructure\TaskExecution\Exceptions\QueueItemSaveException
     */
    protected function updateQueueItem(QueueItem $queueItem, array $additionalWhere)
    {
        $filter = new QueryFilter();
        $filter->where('id', Operators::EQUALS, $queueItem->getId());

        foreach ($additionalWhere as $name => $value) {
            if ($value === null) {
                $filter->where($name, Operators::NULL);
            } else {
                $filter->where($name, Operators::EQUALS, $value);
            }
        }

        /** @var QueueItem $item */
        $item = $this->selectOne($filter);
        if ($item === null) {
            throw new QueueItemSaveException("Cannot update queue item with id {$queueItem->getId()}.");
        }

        $this->update($queueItem);
    }

    /**
     * Retrieves index column map.
     *
     * @return array
     */
    protected function getColumnIndexMap()
    {
        $queueItem = new QueueItem();

        return IndexHelper::mapFieldsToIndexes($queueItem);
    }

    /**
     * Retrieves db_name for DBAL.
     *
     * @return string
     */
    protected function getDbName()
    {
        return Database::TABLE_NAME;
    }

    /**
     * Inflates queue items.
     *
     * @param array $rawItems
     *
     * @return array
     */
    protected function inflateQueueItems(array $rawItems = [])
    {
        $result = [];
        foreach ($rawItems as $rawItem) {
            $item = new QueueItem();
            $item->inflate(json_decode($rawItem['data'], true));
            $item->setId((int)$rawItem['id']);
            $result[] = $item;
        }

        return $result;
    }
}