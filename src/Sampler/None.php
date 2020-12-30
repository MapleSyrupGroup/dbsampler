<?php

namespace Quidco\DbSampler\Sampler;

use Quidco\DbSampler\BaseSampler;

/**
 * Essentially a 'no-op' table sampler - allows tables to be specified as required without copying any data
 */
class None extends BaseSampler implements Sampler
{
    public function getName()
    {
        return 'None';
    }

    public function getRows(): array
    {
        return [];
    }
}
