<?php

namespace Quidco\DbSampler\Cleaner\FieldCleaner;

use Quidco\DbSampler\Cleaner\FieldCleaner;

class DateTime implements FieldCleaner
{
    public const DATE_FORMAT = 'Y-m-d H:i:s';

    public function clean(array $parameters, ?string $originalValue = null)
    {
        $when = isset($parameters[0]) ? $parameters[0] : 'now';
        $epoch = strtotime($when);
        if ($epoch === false) {
            throw new \RuntimeException("Invalid datetime parameter '$when' specified");
        }

        return date(self::DATE_FORMAT, $epoch);
    }
}
