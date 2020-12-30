<?php

namespace Quidco\DbSampler\Cleaner\FieldCleaner;

use Quidco\DbSampler\Cleaner\FieldCleaner;

class RandomDigits implements FieldCleaner
{
    public function clean(array $parameters, ?string $originalValue = null)
    {
        $digits = empty($parameters[0]) ? 5 : $parameters[0];
        return implode(
            '',
            array_map(
                function () {
                    return mt_rand(0, 9);
                },
                range(1, $digits)
            )
        );
    }
}
