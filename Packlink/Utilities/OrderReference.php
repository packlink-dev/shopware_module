<?php

namespace Packlink\Utilities;

/**
 * Class OrderReference
 *
 * Gives every Shopware order a random 9-letter reference, the way PrestaShop does, and sends it to
 * Packlink as the order ID and the shipment custom reference. Shopware order IDs and numbers restart
 * on every installation, and Packlink returns the existing shipment for an order ID it has already
 * seen from the same source, so a reinstalled or second shop would otherwise get old shipments back.
 *
 * @package Packlink\Utilities
 */
class OrderReference
{
    const TABLE = 's_order_attributes';
    const COLUMN = 'packlink_reference';
    const LENGTH = 9;
    const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';

    /**
     * @var bool|null
     */
    private static $columnExists;

    /**
     * Creates the order attribute that holds the reference and shows it in the backend.
     */
    public static function createAttribute()
    {
        /** @var \Shopware\Bundle\AttributeBundle\Service\CrudService $crud */
        $crud = Shopware()->Container()->get('shopware_attribute.crud_service');
        $crud->update(self::TABLE, self::COLUMN, 'string', [
            'label' => 'Packlink reference',
            'supportText' => 'Reference of the order in Packlink PRO.',
            'displayInBackend' => true,
            'translatable' => false,
            'position' => 100,
        ]);

        Shopware()->Models()->generateAttributeModels([self::TABLE]);
        self::$columnExists = true;
    }

    /**
     * Removes the order attribute.
     */
    public static function deleteAttribute()
    {
        /** @var \Shopware\Bundle\AttributeBundle\Service\CrudService $crud */
        $crud = Shopware()->Container()->get('shopware_attribute.crud_service');
        if ($crud->get(self::TABLE, self::COLUMN) !== null) {
            $crud->delete(self::TABLE, self::COLUMN);
            Shopware()->Models()->generateAttributeModels([self::TABLE]);
        }

        self::$columnExists = false;
    }

    /**
     * Returns the stored reference of the order, or an empty string.
     *
     * @param int|string $orderId
     *
     * @return string
     */
    public static function get($orderId)
    {
        if (!self::columnExists()) {
            return '';
        }

        return (string)Shopware()->Db()->fetchOne(
            'SELECT ' . self::COLUMN . ' FROM ' . self::TABLE . ' WHERE orderID = ?',
            [(int)$orderId]
        );
    }

    /**
     * Returns the reference of the order, creating and storing one when the order has none yet.
     *
     * @param int|string $orderId
     *
     * @return string Reference, or an empty string when the attribute does not exist (plugin not updated).
     */
    public static function getOrCreate($orderId)
    {
        if (!self::columnExists()) {
            return '';
        }

        $reference = self::get($orderId);
        if ($reference !== '') {
            return $reference;
        }

        $reference = self::generateUnique();
        $db = Shopware()->Db();
        $updated = $db->update(self::TABLE, [self::COLUMN => $reference], ['orderID = ?' => (int)$orderId]);
        if ($updated === 0) {
            $db->insert(self::TABLE, ['orderID' => (int)$orderId, self::COLUMN => $reference]);
        }

        return $reference;
    }

    /**
     * Generates a reference that no other order uses.
     *
     * @return string
     */
    private static function generateUnique()
    {
        do {
            $reference = self::generate();
            $taken = Shopware()->Db()->fetchOne(
                'SELECT 1 FROM ' . self::TABLE . ' WHERE ' . self::COLUMN . ' = ?',
                [$reference]
            );
        } while ($taken);

        return $reference;
    }

    /**
     * @return string Nine random upper-case letters, like a PrestaShop order reference.
     */
    private static function generate()
    {
        $max = strlen(self::ALPHABET) - 1;
        $reference = '';
        for ($i = 0; $i < self::LENGTH; $i++) {
            $index = function_exists('random_int') ? random_int(0, $max) : mt_rand(0, $max);
            $reference .= self::ALPHABET[$index];
        }

        return $reference;
    }

    /**
     * @return bool Whether the attribute column exists (it does after install or the 1.5.2 update).
     */
    private static function columnExists()
    {
        if (self::$columnExists === null) {
            $columns = Shopware()->Db()->fetchCol('SHOW COLUMNS FROM ' . self::TABLE . ' LIKE ?', [self::COLUMN]);
            self::$columnExists = !empty($columns);
        }

        return self::$columnExists;
    }
}
