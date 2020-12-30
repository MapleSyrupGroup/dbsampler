<?php


namespace Quidco\DbSampler\SamplerMap;

use Quidco\DbSampler\Sampler\CleanAll;
use Quidco\DbSampler\Sampler\CleanMatchedRows;
use Quidco\DbSampler\Sampler\AllRows;
use Quidco\DbSampler\Sampler\None;
use Quidco\DbSampler\Sampler\MatchedRows;
use Quidco\DbSampler\Sampler\NewestById;

class SamplerMap
{
    public const MAP = [
        'all' => AllRows::class,
        'none' => None::class,
        'matched' => MatchedRows::class,
        'newestbyid' => NewestById::class,
        'cleanall' => CleanAll::class,
        'cleanmatched' => CleanMatchedRows::class,
    ];
}
