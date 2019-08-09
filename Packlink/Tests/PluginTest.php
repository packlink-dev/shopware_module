<?php

namespace Packlink;

use Packlink\Packlink as Plugin;
use Shopware\Components\Test\Plugin\TestCase;

class PluginTest extends TestCase
{
    protected static $ensureLoadedPlugins = [
        'Packlink' => []
    ];

    /**
     * @throws \Exception
     */
    public function testCanCreateInstance()
    {
        /** @var Plugin $plugin */
        $plugin = Shopware()->Container()->get('kernel')->getPlugins()['Packlink'];

        $this->assertInstanceOf(Plugin::class, $plugin);
    }
}
