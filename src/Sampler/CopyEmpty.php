<?php
namespace Quidco\DbSampler\Sampler;

use Quidco\DbSampler\BaseSampler;

/**
 * Essentially a 'no-op' table sampler - allows tables to be specified as required without copying any data
 */
class CopyEmpty extends BaseSampler
{
    public function getName()
    {
        return 'CopyEmpty';
    }

    public function loadConfig($config)
    {
        // none needed
    }

    public function getRows()
    {
        return [];
    }
}
