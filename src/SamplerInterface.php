<?php

namespace Quidco\DbSampler;

interface SamplerInterface
{
    /**
     * Return a unique name for this sampler for informational purposes
     *
     * @return string
     * @inheritdoc
     */
    public function getName();

    /**
     * Return all rows that this sampler would copy
     *
     * @return array[]
     * @inheritdoc
     */
    public function getRows();

    /**
     * Copy sampled data from source to destination DB according to internal logic
     *
     * @return int Rows copied
     * @inheritdoc
     */
    public function execute();
}
