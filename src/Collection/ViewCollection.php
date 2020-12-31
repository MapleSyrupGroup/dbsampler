<?php

namespace Quidco\DbSampler\Collection;

use Quidco\DbSampler\Configuration\MigrationConfiguration;

class ViewCollection
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
