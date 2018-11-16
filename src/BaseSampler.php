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
     * Table name
     *
     * @return string
     */
    public function getTableName()
    {
        return $this->tableName;
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
     * Get connection to source DB
     *
     * @return Connection
     */
    public function getSourceConnection()
    {
        return $this->sourceConnection;
    }

    /**
     * Set connection to source DB
     *
     * @param Connection $sourceConnection Source connection
     *
     * @return BaseSampler
     */
    public function setSourceConnection(Connection $sourceConnection)
    {
        $this->sourceConnection = $sourceConnection;

        return $this;
    }

    /**
     * Get connection to dest DB
     *
     * @return Connection
     */
    public function getDestConnection()
    {
        return $this->destConnection;
    }

    /**
     * Set connection to dest DB
     *
     * @param Connection $destConnection Dest connection
     *
     * @return BaseSampler
     */
    public function setDestConnection(Connection $destConnection)
    {
        $this->destConnection = $destConnection;

        return $this;
    }

    /**
     * Get loaded ReferenceStore
     *
     * @return ReferenceStore
     */
    public function getReferenceStore()
    {
        return $this->referenceStore;
    }

    /**
     * Set a ReferenceStore to save ids as required
     *
     * @param ReferenceStore $referenceStore ReferenceStore object
     *
     * @return BaseSampler
     */
    public function setReferenceStore(ReferenceStore $referenceStore)
    {
        $this->referenceStore = $referenceStore;

        return $this;
    }

    /**
     * Load config common to all child classes
     *
     * @param \stdClass $config Configuration from migration file
     *
     * @return void
     */
    public function loadConfig($config)
    {
        $this->referenceFields = isset($config->remember) ? $config->remember : [];
        $this->limit = isset($config->limit) ? (int)$config->limit : false;
        $this->postImportSql = isset($config->postImportSql) ? $config->postImportSql : [];
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

            $this->sanitiseRowKeys($row);
            $this->demandDestConnection()->insert($this->tableName, $row);
        }

        foreach ($references as $reference => $values) {
            $this->referenceStore->setReferencesByName($reference, $values);
        }

        foreach ($this->postImportSql as $sql) {
            $this->demandDestConnection()->exec($sql);
        }

        return count($rows);
    }

    /**
     * A more insistent get() - throws exception if not configured
     *
     * @return Connection
     * @throws \RuntimeException If exception not configured
     */
    protected function demandDestConnection()
    {
        if (!$this->destConnection) {
            throw new \RuntimeException('Dest connection not present');
        }

        return $this->destConnection;
    }

    /**
     * Convenience method to assert presence of a config key while fetching
     *
     * @param \stdClass $config Config block
     * @param string    $key    Key to be found in block
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
            $row[$this->demandDestConnection()->quoteIdentifier($key)] = $row[$key];
            unset($row[$key]);
        }
    }
}
