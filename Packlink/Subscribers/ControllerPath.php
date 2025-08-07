<?php

namespace Packlink\Subscribers;

use Enlight\Event\SubscriberInterface;
use Enlight_Event_EventArgs;
use Packlink\Infrastructure\ServiceRegister;
use Packlink\Infrastructure\TaskExecution\Interfaces\TaskRunnerWakeup;

class ControllerPath implements SubscriberInterface
{
    /** @var \Packlink\Infrastructure\TaskExecution\Interfaces\TaskRunnerWakeup */
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
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_PacklinkModuleStateController' => 'onGetControllerPromotion',
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_PacklinkConfiguration' => 'onGetControllerPromotion',
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_PacklinkDefaultParcel' => 'onGetControllerPromotion',
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_PacklinkDefaultWarehouse' => 'onGetControllerPromotion',
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_PacklinkTax' => 'onGetControllerPromotion',
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_PacklinkShippingMethod' => 'onGetControllerPromotion',
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_PacklinkOrderStatusMap' => 'onGetControllerPromotion',
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_PacklinkShopShippingMethod' => 'onGetControllerPromotion',
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_PacklinkDashboard' => 'onGetControllerPromotion',
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_PacklinkDebug' => 'onGetControllerPromotion',
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_PacklinkDraftTaskStatusController' => 'onGetControllerPromotion',
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_PacklinkDraftTaskCreateController' => 'onGetControllerPromotion',
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_PacklinkDraftDetailsController' => 'onGetControllerPromotion',
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_PacklinkPrintLabelsController' => 'onGetControllerPromotion',
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_PacklinkAutoTest' => 'onGetControllerPromotion',
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_PacklinkAutoConfigure' => 'onGetControllerPromotion',
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_PacklinkOnboardingController' => 'onGetControllerPromotion',
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_PacklinkRegistrationController' => 'onGetControllerPromotion',
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_PacklinkRegistrationRegionsController' => 'onGetControllerPromotion',
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_PacklinkShippingCountriesController' => 'onGetControllerPromotion',
            'Enlight_Controller_Dispatcher_ControllerPath_Frontend_PacklinkAsyncProcess' => 'onGetControllerPromotion',
            'Enlight_Controller_Dispatcher_ControllerPath_Frontend_PacklinkWebhooks' => 'onGetControllerPromotion',
            'Enlight_Controller_Dispatcher_ControllerPath_Frontend_PacklinkLocations' => 'onGetControllerPromotion',
            'Enlight_Controller_Dispatcher_ControllerPath_Frontend_PacklinkDropoff' => 'onGetControllerPromotion',
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_PacklinkSystemInfoController' => 'onGetControllerPromotion',
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_PacklinkManualRefreshController' => 'onGetControllerPromotion',
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
        $this->getWakeupService()->wakeup();

        $eventName = $arguments->getName();

        $moduleAndController = str_replace('Enlight_Controller_Dispatcher_ControllerPath_', '', $eventName);
        list($module, $controller) = explode('_', $moduleAndController);

        return "{$this->pluginDirectory}/Controllers/{$module}/{$controller}.php";
    }

    /**
     * Retrieves wakeup service;
     *
     * @return \Packlink\Infrastructure\TaskExecution\Interfaces\TaskRunnerWakeup|object
     */
    protected function getWakeupService()
    {
        if ($this->wakeupService === null) {
            $this->wakeupService = ServiceRegister::getService(TaskRunnerWakeup::CLASS_NAME);
        }

        return $this->wakeupService;
    }
}
