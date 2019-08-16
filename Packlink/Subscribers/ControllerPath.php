<?php

namespace Packlink\Subscribers;

use Enlight\Event\SubscriberInterface;
use Enlight_Event_EventArgs;
use Logeecom\Infrastructure\ServiceRegister;
use Logeecom\Infrastructure\TaskExecution\Interfaces\TaskRunnerWakeup;
use Packlink\Bootstrap\Bootstrap;

class ControllerPath implements SubscriberInterface
{
    /** @var \Logeecom\Infrastructure\TaskExecution\Interfaces\TaskRunnerWakeup */
    protected $wakeupService;
    /**
     * @var string
     */
    private $pluginDirectory;

    /**
     * @param $pluginDirectory
     */
    public function __construct($pluginDirectory)
    {
        $this->pluginDirectory = $pluginDirectory;
    }

    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_PacklinkMain' => 'onGetControllerPromotion',
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_PacklinkLogin' => 'onGetControllerPromotion',
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_PacklinkConfiguration' => 'onGetControllerPromotion',
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_PacklinkDefaultParcel' => 'onGetControllerPromotion',
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_PacklinkDefaultWarehouse' => 'onGetControllerPromotion',
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_PacklinkTax' => 'onGetControllerPromotion',
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_PacklinkShippingMethod' => 'onGetControllerPromotion',
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_PacklinkOrderStatusMap' => 'onGetControllerPromotion',
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_PacklinkShopShippingMethod' => 'onGetControllerPromotion',
            'Enlight_Controller_Dispatcher_ControllerPath_Frontend_PacklinkAsyncProcess' => 'onGetControllerPromotion',
            'Enlight_Controller_Dispatcher_ControllerPath_Frontend_PacklinkImage' => 'onGetControllerPromotion',
        ];
    }

    /**
     * Controller path handler, generates controller path based on a event name
     *
     * @param \Enlight_Event_EventArgs $arguments
     *
     * @return string Controller path
     */
    public function onGetControllerPromotion(Enlight_Event_EventArgs $arguments)
    {
        Bootstrap::init();

        $this->getWakeupService()->wakeup();

        $eventName = $arguments->getName();

        $moduleAndController = str_replace('Enlight_Controller_Dispatcher_ControllerPath_', '', $eventName);
        list($module, $controller) = explode('_', $moduleAndController);

        return "{$this->pluginDirectory}/Controllers/{$module}/{$controller}.php";
    }

    /**
     * Retrieves wakeup service;
     *
     * @return \Logeecom\Infrastructure\TaskExecution\Interfaces\TaskRunnerWakeup|object
     */
    protected function getWakeupService()
    {
        if ($this->wakeupService === null) {
            $this->wakeupService = ServiceRegister::getService(TaskRunnerWakeup::CLASS_NAME);
        }

        return $this->wakeupService;
    }
}
