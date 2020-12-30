<?php

namespace Quidco\DbSampler\Cleaner\FieldCleaner;

use Quidco\DbSampler\Cleaner\FieldCleaner;

class NullCleaner implements FieldCleaner
{
    public function clean(array $parameters, ?string $originalValue = null)
    {
        return null;
    }
}
