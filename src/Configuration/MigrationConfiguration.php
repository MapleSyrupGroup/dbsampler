<?php


namespace Quidco\DbSampler\Configuration;

class MigrationConfiguration
{
    private $config;

    private function __construct(\stdClass $config)
    {
        $this->config = $config;
    }

    public static function fromJson(string $configJson): self
    {
        $config = \json_decode($configJson);

        if (null === $config) {
            throw new \RuntimeException("Migration JSON config was not valid");
        }

        if (!isset($config->name)) {
            throw new \RuntimeException("Migration file has no name field");
        }

        return new self($config);
    }

    public function getName(): string
    {
        return $this->config->name;
    }

    public function getTables(): array
    {
        return (array)$this->config->tables;
    }

    public function getViews(): array
    {
        return $this->config->views ?? [];
    }

    public function getSourceDbName(): string
    {
        return $this->config->sourceDb;
    }

    public function getDestinationDbName(): string
    {
        return $this->config->destDb;
    }
}
