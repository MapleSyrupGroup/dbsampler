<?php

namespace Quidco\DbSampler\Cleaner\FieldCleaner;

use Quidco\DbSampler\Cleaner\FieldCleaner;

class EmptyString implements FieldCleaner
{
    public function clean(array $parameters, ?string $originalValue = null)
    {
        return '';
    }
}
