<?php

namespace Quidco\DbSampler\DatabaseSchema;

use Quidco\DbSampler\Configuration\MigrationConfiguration;

class ViewsList
{
    private $views = [];

    private function __construct(array $views)
    {
        $this->views = $views;
    }

    public static function fromConfig(MigrationConfiguration $configuration): self
    {
        return new self($configuration->getViews());
    }

    public function getViews(): array
    {
        return $this->views;
    }
}
