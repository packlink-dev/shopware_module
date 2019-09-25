<?php

namespace Packlink\Subscribers;

use Enlight\Event\SubscriberInterface;

class FrontendCsrfProvider implements SubscriberInterface
{
    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Action_PostDispatch_Frontend_Account' => 'setCsrfToken',
            'Enlight_Controller_Action_PostDispatch_Frontend_Checkout' => 'setCsrfToken',
        ];
    }

    /**
     * Sets csrf token.
     *
     * @param \Enlight_Controller_ActionEventArgs $args
     */
    public function setCsrfToken(\Enlight_Controller_ActionEventArgs $args)
    {
        $token = Shopware()->Session()->get('X-CSRF-Token');
        if ($token) {
            $args->getSubject()->View()->assign(['plCsrf' => $token]);
        }
    }
}