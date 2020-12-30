<?php

namespace Quidco\DbSampler\Cleaner\FieldCleaner;

class Zero implements \Quidco\DbSampler\Cleaner\FieldCleaner
{

    public function clean(array $parameters, ?string $originalValue = null)
    {
        return 0;
    }
}