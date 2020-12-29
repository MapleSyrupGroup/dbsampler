<?php

namespace Quidco\DbSampler\Collection;

use Quidco\DbSampler\Configuration\MigrationConfiguration;
use Quidco\DbSampler\ReferenceStore;
use Quidco\DbSampler\SamplerInterface;
use Quidco\DbSampler\SamplerMap\SamplerMap;

class TableCollection
{
    private $tables = [];

    /**
     * @var ReferenceStore
     */
    private $referenceStore;
    /**
     * @var array
     */
    private $rawTables;

    private function __construct(array $rawTables)
    {
        $this->referenceStore = new ReferenceStore();
        $this->rawTables = $rawTables;
    }

    public static function fromConfig(MigrationConfiguration $configuration): self
    {
        return new self((array)$configuration->getTables());
    }

    public function getTables(): array
    {
        // @todo we probably shouldn't be building the sampler in this getter. find another place to do it!
        if ([] === $this->tables) {
            foreach ($this->rawTables as $table => $migrationSpec) {
                $this->tables[$table] = $this->buildTableSampler($migrationSpec);
            }
        }

        return $this->tables;
    }

    /**
     * Build a SamplerInterface object from configuration
     *
     * @throws \UnexpectedValueException If bad object created - should be impossible
     * @throws \RuntimeException On invalid specification
     */
    private function buildTableSampler(\stdClass $migrationSpec): SamplerInterface
    {
        $sampler = null;

        // @todo: $migrationSpec should be an object with a getSampler() method
        $samplerType = strtolower($migrationSpec->sampler);
        if (array_key_exists($samplerType, SamplerMap::MAP)) {
            $samplerClass = SamplerMap::MAP[$samplerType];
            $sampler = new $samplerClass;
            if (!$sampler instanceof SamplerInterface) {
                throw new \UnexpectedValueException('Invalid sampler created');
            }
            /** @var SamplerInterface $sampler */
            $sampler->loadConfig($migrationSpec);
            $sampler->setReferenceStore($this->referenceStore);
        } else {
            throw new \RuntimeException("Unrecognised sampler type '$samplerType' required");
        }

        return $sampler;
    }
}
