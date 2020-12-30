<?php

namespace Quidco\DbSampler;

use Doctrine\DBAL\Connection;

/**
 * Abstract BaseSampler class with some common functionality.
 *
 * Not for use as a type hint, use SamplerInterface for that
 */
abstract class BaseSampler implements SamplerInterface
{
    /**
     * Table on which the sampler is operating
     *
     * @var string
     */
    protected $tableName;

    /**
     * Connection to Source DB
     *
     * @var Connection
     */
    protected $sourceConnection;

    /**
     * @var ReferenceStore
     */
    protected $referenceStore;

    /**
     * @var array
     */
    protected $referenceFields = [];

    /**
     * Max number to match (default Db order)
     *
     * @var integer
     */
    protected $limit;

    /**
     * @var \stdClass
     */
    protected $config;


    public function __construct(
        \stdClass $config,
        ReferenceStore $referenceStore,
        Connection $sourceConnection
    )
    {
        $this->config = $config;
        $this->referenceStore = $referenceStore;

        $this->referenceFields = isset($config->remember) ? $config->remember : [];
        $this->limit = isset($config->limit) ? (int)$config->limit : false;
        $this->sourceConnection = $sourceConnection;
    }

    /**
     * Set table name
     *
     * @param string $tableName Name of table to operate on
     *
     * @return BaseSampler
     */
    public function setTableName($tableName)
    {
        $this->tableName = $tableName;

        return $this;
    }

    /**
     * Naïve implementation - grab all rows and insert
     *
     */
    public function execute(): array
    {
        $rows = $this->getRows();
        $references = [];

        foreach ($this->referenceFields as $key => $variable) {
            if (!array_key_exists($variable, $references)) {
                $references[$variable] = [];
            }
        }

        foreach ($rows as $row) {
            // Store any reference fields we've been told to remember
            foreach ($this->referenceFields as $key => $variable) {
                $references[$variable][] = $row[$key];
            }
        }

        foreach ($references as $reference => $values) {
            $this->referenceStore->setReferencesByName($reference, $values);
        }

        return $rows;
    }

    /**
     * Convenience method to assert presence of a config key while fetching
     *
     * @param \stdClass $config Config block
     * @param string $key Key to be found in block
     *
     * @return mixed
     * @throws \RuntimeException If required key missing
     */
    protected function demandParameterValue($config, $key)
    {
        if (!isset($config->$key)) {
            throw new \RuntimeException("'$key' missing from config required by " . get_called_class());
        }

        return $config->$key;
    }
}
