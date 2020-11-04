<?php

namespace Packlink\Utilities;

use Packlink\Core\BusinessLogic\DTO\ValidationError;

class Translation
{
    /**
     * Returns translation for validation error message.
     *
     * @param string $code
     * @param string $field
     *
     * @return string
     */
    public static function getValidationErrorTranslation($code, $field)
    {
        if ($code === ValidationError::ERROR_REQUIRED_FIELD) {
            return self::get('error/required');
        }

        if (in_array($field, ['width', 'length', 'height'])) {
            return self::get('error/integer');
        }

        return self::get('error/' . $field);
    }

    /**
     * @param $key
     *
     * @return string
     */
    public static function get($key)
    {
        return Shopware()->Snippets()->getNamespace('backend/packlink/configuration')->get($key, $key);
    }
}
