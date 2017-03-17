<?php
namespace Quidco\DbSampler;

use Doctrine\DBAL\Connection;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Migrator class to handle all migrations in a set
 */
class Migrator implements LoggerAwareInterface
{
    /**
     * Name of migration set
     *
     * @var string
     */
    protected $migrationSetName;

    /**
     * Name of source DB
     *
     * @var string
     */
    protected $sourceDb;

    /**
     * Name of dest DB
     *
     * @var string
     */
    protected $destinationDb;

    /**
     * Table samplers
     *
     * @var SamplerInterface[]
     */
    protected $tableMigrations;

    /**
     * Object that can create a Doctrine\DBAL\Connection from DB name
     *
     * @var DatabaseConnectionFactoryInterface
     */
    protected $databaseConnectionFactory;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Migrator constructor.
     *
     * @param string $migrationSetName Name
     */
    public function __construct($migrationSetName)
    {
        $this->migrationSetName = $migrationSetName;
    }

    /**
     * @return string
     */
    public function getSourceDb()
    {
        return $this->sourceDb;
    }

    /**
     * @param string $sourceDb
     *
     * @return Migrator
     */
    public function setSourceDb($sourceDb)
    {
        $this->sourceDb = $sourceDb;

        return $this;
    }

    /**
     * @return string
     */
    public function getDestinationDb()
    {
        return $this->destinationDb;
    }

    /**
     * @param string $destinationDb
     *
     * @return Migrator
     */
    public function setDestinationDb($destinationDb)
    {
        $this->destinationDb = $destinationDb;

        return $this;
    }

    /**
     * @return DatabaseConnectionFactoryInterface
     */
    public function getDatabaseConnectionFactory()
    {
        return $this->databaseConnectionFactory;
    }

    /**
     * @param DatabaseConnectionFactoryInterface $databaseConnectionFactory
     *
     * @return Migrator
     */
    public function setDatabaseConnectionFactory(DatabaseConnectionFactoryInterface $databaseConnectionFactory)
    {
        $this->databaseConnectionFactory = $databaseConnectionFactory;

        return $this;
    }

    /**
     * Perform the configured migrations
     */
    public function execute()
    {
        $sourceConnection = $this->databaseConnectionFactory->createConnectionByDbName($this->sourceDb);
        $destConnection = $this->databaseConnectionFactory->createConnectionByDbName($this->destinationDb);

        $setName = $this->migrationSetName;

        foreach ($this->tableMigrations as $table => $sampler) {
            $this->ensureEmptyTargetTable($table, $sourceConnection, $destConnection);
            $sampler->setTableName($table);
            $sampler->setSourceConnection($sourceConnection);
            $sampler->setDestConnection($destConnection);
            $rows = $sampler->execute();
            $this->getLogger()->info("$setName: migrated '$table' with '" . $sampler->getName() . "': $rows rows");
        }
    }

    /**
     * Reset the list of table samplers to be empty
     */
    public function clearTableMigrations()
    {
        $this->tableMigrations = [];
    }

    /**
     * Add a SamplerInterface object to handle a named table
     *
     * @param string           $table   Table name
     * @param SamplerInterface $sampler Sampler class
     *
     * @return void
     */
    public function addTableSampler($table, SamplerInterface $sampler)
    {
        //TODO these might need to be in order
        $this->tableMigrations[$table] = $sampler;
    }

    /**
     * Ensure that the specified table is present in the destination DB as an empty copy of the source
     *
     * @param string     $table            Table name
     * @param Connection $sourceConnection Originating DB connection
     * @param Connection $destConnection   Target DB connection
     *
     * @return void
     * @throws \Doctrine\DBAL\DBALException If target table cannot be removed or recreated
     */
    private function ensureEmptyTargetTable($table, Connection $sourceConnection, Connection $destConnection)
    {
        // SchemaManager doesn't do enums!
        $destConnection->exec('DROP TABLE IF EXISTS ' . $sourceConnection->quoteIdentifier($table));

        $driverName = $sourceConnection->getDriver()->getName();
        if ($driverName === 'pdo_mysql') {
            $createSqlRow = $sourceConnection->query('SHOW CREATE TABLE ' . $sourceConnection->quoteIdentifier($table))
                ->fetch(\PDO::FETCH_ASSOC);
            $createSql = $createSqlRow['Create Table'];
        } elseif ($driverName === 'pdo_sqlite') {
            /** @noinspection SqlDialectInspection */
            $schemaSql = 'SELECT sql FROM sqlite_master WHERE name=' . $sourceConnection->quoteIdentifier($table);
            $createSql = $sourceConnection->query($schemaSql)->fetchColumn();
        } else {
            throw new \RuntimeException(__METHOD__ . " not implemented for $driverName yet");
        }

        $destConnection->exec($createSql);
    }


    /**
     * Sets a logger instance on the object.
     *
     * @param LoggerInterface $logger Logger instance
     *
     * @return void
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Safely obtain either a configured or null logger
     *
     * @return LoggerInterface
     */
    protected function getLogger()
    {
        return $this->logger ?: new NullLogger();
    }
}
