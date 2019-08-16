<?php

namespace Packlink\Controllers\Common;

use Packlink\BusinessLogic\Http\DTO\BaseDto;

trait CanFormatResponse
{
    /**
     * Formats dto for response.
     *
     * @param BaseDto | BaseDto[] $data
     *
     * @return array
     */
    protected function formatResponse($data)
    {
        if (is_array($data)) {
            $result = [];
            foreach ($data as $item) {
                $result[] = $item->toArray();
            }
        } else {
            $result = $data->toArray();
        }

        return $result;
    }
}