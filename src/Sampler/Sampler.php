<?php


namespace Quidco\DbSampler\Sampler;

interface Sampler
{
    public function getRows(): array;
}
