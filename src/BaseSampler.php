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
     * Connection to Dest DB
     *
     * @var Connection
     */
    protected $destConnection;

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
     * Commands to run on the destination table after importing
     *
     * @var array
     */
    protected $postImportSql = [];

    /**
     * @var \stdClass
     */
    protected $config;


    public function __construct(
        \stdClass $config,
        ReferenceStore $referenceStore,
        Connection $sourceConnection,
        Connection $destConnection
    )
    {
        $this->config = $config;
        $this->referenceStore = $referenceStore;

        $this->referenceFields = isset($config->remember) ? $config->remember : [];
        $this->limit = isset($config->limit) ? (int)$config->limit : false;
        $this->postImportSql = isset($config->postImportSql) ? $config->postImportSql : [];
        $this->sourceConnection = $sourceConnection;
        $this->destConnection = $destConnection;
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
     * @return int Rows copied
     * @throws \RuntimeException If dest connection not configured
     */
    public function execute()
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

        foreach ($rows as $row) {
            $this->sanitiseRowKeys($row);
            $this->destConnection->insert($this->tableName, $row);
        }

        foreach ($this->postImportSql as $sql) {
            $this->destConnection->exec($sql);
        }

        return count($rows);
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

    /**
     * Issue: DBAL insert() does not check for reserved words being used as column names.
     *
     * So we have to clean the keys ourselves.
     *
     * *Very* special case initially as the general case is likely to be slow
     *
     * @param mixed[] $row Row to clean
     *
     * @return void
     *
     * @throws \RuntimeException If dest connection not configured
     */
    private function sanitiseRowKeys(&$row)
    {
        /** @noinspection ForeachOnArrayComponentsInspection */
        foreach (array_keys($row) as $key) {
            $row[$this->destConnection->quoteIdentifier($key)] = $row[$key];
            unset($row[$key]);
        }
    }
}
