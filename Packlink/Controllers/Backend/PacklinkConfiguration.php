<?php

use Shopware\Components\CSRFWhitelistAware;

class Shopware_Controllers_Backend_PacklinkConfiguration extends Enlight_Controller_Action implements CSRFWhitelistAware
{
    /**
     * Returns a list with actions which should not be validated for CSRF protection
     *
     * @return string[]
     */
    public function getWhitelistedCSRFActions()
    {
        return ['index'];
    }

    public function indexAction()
    {
        $img = \Packlink\Utilities\Url::getFrontUrl('PacklinkImage', 'index') . '?image=' . urlencode('/de/dpd.png');

        $this->View()->assign(['img' => $img]);
    }
}