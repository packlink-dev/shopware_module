<?php

namespace Packlink\Utilities;

use Packlink\Infrastructure\Data\DataTransferObject;

class Response
{
    /**
     * Converts DTOs to array.
     *
     * @param DataTransferObject[] $entities
     */
    public static function dtoEntitiesResponse(array $entities)
    {
        $response = [];

        foreach ($entities as $entity) {
            $response[] = $entity->toArray();
        }

        return $response;
    }

    /**
     * Returns validation errors.
     *
     * @param \Packlink\BusinessLogic\DTO\ValidationError[] $errors
     */
    public static function validationErrorsResponse(array $errors)
    {
        $result = [];

        foreach ($errors as $error) {
            $result[$error->field] = Translation::getValidationErrorTranslation($error->code, $error->field);
        }

        return $result;
    }
}