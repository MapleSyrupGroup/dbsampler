<?php


namespace Quidco\DbSampler\Sampler;

interface Sampler
{
    public function getName(): string;

    public function getRows(): array;
}
