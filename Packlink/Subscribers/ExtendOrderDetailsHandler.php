<?php

namespace Packlink\Subscribers;

use Enlight\Event\SubscriberInterface;
use Enlight_Controller_ActionEventArgs;
use Packlink\Bootstrap\Bootstrap;

class ExtendOrderDetailsHandler implements SubscriberInterface
{
    /**
     * @var string
     */
    protected $pluginDirectory;

    /**
     * @param $pluginDirectory
     */
    public function __construct($pluginDirectory)
    {
        $this->pluginDirectory = $pluginDirectory;
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return ['Enlight_Controller_Action_PostDispatchSecure_Backend_Order' => 'onOrderPostDispatch'];
    }

    /**
     * Injects proper extjs files for order view extension.
     *
     * @param \Enlight_Controller_ActionEventArgs $args
     */
    public function onOrderPostDispatch(Enlight_Controller_ActionEventArgs $args)
    {
        Bootstrap::init();

        /** @var \Shopware_Controllers_Backend_Order $controller */
        $controller = $args->getSubject();

        $view = $controller->View();
        $request = $controller->Request();

        if ($view && $request->getActionName() === 'index') {
            $view->extendsTemplate('backend/packlink_detail/app.js');
        }

        if ($view && $request->getActionName() === 'load') {
            $view->extendsTemplate('backend/packlink_detail/window.js');
            $view->extendsTemplate('backend/packlink_list/packlink_order_list.js');
            $view->extendsTemplate('backend/packlink_list/models/packlink_order_model.js');
        }
    }
}