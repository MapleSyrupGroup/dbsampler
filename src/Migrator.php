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
    protected $tableMigrations = [];

    /**
     * @var string[]
     */
    protected $viewsToMigrate = [];

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
     * Set source DB name
     *
     * @param string $sourceDb Source DB name
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
     * Set destination DB name
     *
     * @param string $destinationDb Destination DB name
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
     * Set Database Connection Factory
     *
     * @param DatabaseConnectionFactoryInterface $databaseConnectionFactory Database Connection Factory
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
     *
     * @return void
     *
     * @throws \Exception Rethrows any exceptions after loggin
     */
    public function execute()
    {
        $sourceConnection = $this->databaseConnectionFactory->createSourceConnectionByDbName($this->sourceDb);
        $destConnection = $this->databaseConnectionFactory->createDestConnectionByDbName($this->destinationDb);

        $setName = $this->migrationSetName;

        foreach ($this->tableMigrations as $table => $sampler) {
            try {
                $this->ensureEmptyTargetTable($table, $sourceConnection, $destConnection);
                $sampler->setTableName($table);
                $sampler->setSourceConnection($sourceConnection);
                $sampler->setDestConnection($destConnection);
                $rows = $sampler->execute();
                $this->getLogger()->info("$setName: migrated '$table' with '" . $sampler->getName() . "': $rows rows");
            } catch (\Exception $e) {
                $this->getLogger()->error(
                    "$setName: failed to migrate '$table' with '" . $sampler->getName() . "': " . $e->getMessage()
                );
                throw $e;
            }
        }

        foreach ($this->viewsToMigrate as $view) {
            $this->migrateView($view, $sourceConnection, $destConnection);
        }
    }

    /**
     * Reset the list of table samplers to be empty
     *
     * @return void
     */
    public function clearTableMigrations()
    {
        $this->tableMigrations = [];
    }

    /**
     * Reset the list of view migrations to be empty
     *
     * @return void
     */
    public function clearViewMigrations()
    {
        $this->viewsToMigrate = [];
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
     * Add view to be migrated, by name
     *
     * @param string $view Name of view to add
     *
     * @return void
     */
    public function addViewToMigrate($view)
    {
        $this->viewsToMigrate[] = $view;
    }

    /**
     * Ensure that the specified table is present in the destination DB as an empty copy of the source
     *
     * @param string     $table            Table name
     * @param Connection $sourceConnection Originating DB connection
     * @param Connection $destConnection   Target DB connection
     *
     * @return void
     * @throws \RuntimeException If DB type not supported
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
            $schemaSql = 'SELECT SQL FROM sqlite_master WHERE NAME=' . $sourceConnection->quoteIdentifier($table);
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

    /**
     * Migrate a view from source to dest DB
     *
     * @param string     $view             Name of view to migrate
     * @param Connection $sourceConnection Source connection
     * @param Connection $destConnection   Destination connection
     *
     * @return void
     *
     * @throws \Doctrine\DBAL\DBALException If view cannot be read
     * @throws \RuntimeException For DB types where this is unsupported
     */
    protected function migrateView($view, Connection $sourceConnection, Connection $destConnection)
    {
        $destConnection->exec('DROP VIEW IF EXISTS ' . $sourceConnection->quoteIdentifier($view));

        $driverName = $sourceConnection->getDriver()->getName();
        if ($driverName === 'pdo_mysql') {
            $createSqlRow = $sourceConnection->query('SHOW CREATE VIEW ' . $sourceConnection->quoteIdentifier($view))
                ->fetch(\PDO::FETCH_ASSOC);
            $createSql = $createSqlRow['Create View'];

            $currentDestUser = $destConnection->fetchColumn('SELECT CURRENT_USER()');

            if ($currentDestUser) {
                //Because MySQL. SELECT CURRENT_USER() returns an unescaped user
                $currentDestUser = implode('@', array_map(function ($p) use ($destConnection) {
                    return $destConnection->getDatabasePlatform()->quoteSingleIdentifier($p);
                }, explode('@', $currentDestUser)));

                $createSql = preg_replace('/\bDEFINER=`[^`]+`@`[^`]+`(?=\s)/', "DEFINER=$currentDestUser", $createSql);
            }
        } elseif ($driverName === 'pdo_sqlite') {
            $schemaSql = 'SELECT SQL FROM sqlite_master WHERE NAME=' . $sourceConnection->quoteIdentifier($view);
            $createSql = $sourceConnection->query($schemaSql)->fetchColumn();
        } else {
            throw new \RuntimeException(__METHOD__ . " not implemented for $driverName yet");
        }
        $destConnection->exec($createSql);

        $setName = $this->migrationSetName;
        $this->logger->info("$setName: migrated view '$view'");
    }
}
