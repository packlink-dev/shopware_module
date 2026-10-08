<?php

namespace Packlink\Subscribers;

use Enlight\Event\SubscriberInterface;
use Enlight_Event_EventArgs;
use Packlink\Infrastructure\Logger\Logger;
use Packlink\Utilities\OrderReference;

/**
 * Class OrderReferenceHandler
 *
 * Gives every order placed in the storefront its Packlink reference right away, so it is shown in
 * the order list from the start, as PrestaShop's order reference is. Orders created any other way
 * (backend, API, before the update) get one when their draft is created.
 *
 * @package Packlink\Subscribers
 */
class OrderReferenceHandler implements SubscriberInterface
{
    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
            'Shopware_Modules_Order_SaveOrder_ProcessDetails' => 'onOrderSaved',
        ];
    }

    /**
     * @param \Enlight_Event_EventArgs $args
     */
    public function onOrderSaved(Enlight_Event_EventArgs $args)
    {
        $orderId = $args->get('orderId');
        if (empty($orderId)) {
            return;
        }

        try {
            OrderReference::getOrCreate($orderId);
        } catch (\Exception $e) {
            // Never break the checkout; the reference is created later with the draft.
            Logger::logWarning('Could not create Packlink order reference: ' . $e->getMessage(), 'Integration');
        }
    }
}
