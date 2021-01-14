<?php

namespace Quidco\DbSampler;

use Quidco\DbSampler\Sampler\CleanAll;
use Quidco\DbSampler\Sampler\CleanMatched;
use Quidco\DbSampler\Sampler\CopyAll;
use Quidco\DbSampler\Sampler\CopyEmpty;
use Quidco\DbSampler\Sampler\Matched;
use Quidco\DbSampler\Sampler\NewestById;

class SamplerMap
{
    public const MAP = [
        'all' => CopyAll::class,
        'empty' => CopyEmpty::class,
        'matched' => Matched::class,
        'newestbyid' => NewestById::class,
        'cleanall' => CleanAll::class,
        'cleanmatched' => CleanMatched::class,
    ];
}
