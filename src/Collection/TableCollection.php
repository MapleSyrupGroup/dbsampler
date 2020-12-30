<?php

namespace Quidco\DbSampler\Collection;

use Quidco\DbSampler\Configuration\MigrationConfiguration;

class TableCollection
{
    private $tables = [];

    private function __construct(array $tables)
    {
        $this->tables = $tables;
    }

    public static function fromConfig(MigrationConfiguration $configuration): self
    {
        if ([] === $configuration->getTables()) {
            throw new \RuntimeException('No table config was defined');
        }

        return new self((array)$configuration->getTables());
    }

    public function getTables(): array
    {
        return $this->tables;
    }
}
