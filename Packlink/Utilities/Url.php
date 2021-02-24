<?php

namespace Packlink\Utilities;

class Url
{
    /**
     * Retrieves Front controller url.
     *
     * @param $controller
     * @param $action
     * @param array $params
     *
     * @return string
     */
    public static function getFrontUrl($controller, $action, array $params = [])
    {
        $params = array_merge([
            'module' => 'frontend',
            'controller' => $controller,
            'action' => $action,
        ],
        $params);

        $url = Shopware()->Front()->Router()->assemble($params);

        return str_replace('http:', 'https:', $url);
    }

    /**
     * Get backend controller url.
     *
     * @param $controller
     * @param $action
     *
     * @return mixed|string
     */
    public static function getBackendUrl($controller, $action)
    {
        $csrfToken = Shopware()->Container()->get('BackendSession')->offsetGet('X-CSRF-Token');

        $params = array_merge([
           'module' => 'backend',
           'controller' => $controller,
           'action' => $action,
        ],
        ['__csrf_token' => $csrfToken]);

        return Shopware()->Front()->Router()->assemble($params);
    }
}
