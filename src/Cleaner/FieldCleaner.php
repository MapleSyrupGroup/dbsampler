<?php

namespace Quidco\DbSampler\Cleaner;

interface FieldCleaner
{
    public function clean(array $parameters, ?string $originalValue);
}
