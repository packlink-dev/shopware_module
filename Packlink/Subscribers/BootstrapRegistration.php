<?php

namespace Packlink\Subscribers;

use Enlight\Event\SubscriberInterface;
use Packlink\Bootstrap\Bootstrap;

class BootstrapRegistration implements SubscriberInterface
{
    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
       return [
            'Enlight_Controller_Front_StartDispatch' => 'registerBootstrap',
            'Shopware_Console_Add_Command' => 'registerBootstrap',
        ];
    }

    /**
     * Initializes bootstrap.
     */
    public function registerBootstrap()
    {
        Bootstrap::init();
    }
}