<?php

namespace Packlink\Subscribers;

use Enlight\Event\SubscriberInterface;
use Enlight_Controller_ActionEventArgs;

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
     *
     * @throws \Exception
     */
    public function setCsrfToken(Enlight_Controller_ActionEventArgs $args)
    {
        $token = Shopware()->Session()->get('X-CSRF-Token');

        if (!$token) {
            /** @var \Shopware\Bundle\StoreFrontBundle\Struct\ShopContext $context */
            $context = Shopware()->Container()->get('shopware_storefront.context_service')->getShopContext();
            $token = $args->getSubject()->Request()->getCookie('__csrf_token-' . $context->getShop()->getId());
        }

        if (!$token) {
            $token = $args->getSubject()->Request()->getCookie('__csrf_token-1');
        }

        if ($token) {
            $args->getSubject()->View()->assign(['plCsrf' => $token]);
        }
    }
}