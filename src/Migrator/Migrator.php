<?php

namespace Quidco\DbSampler\Migrator;

use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Quidco\DbSampler\Cleaner\CleanerFactory;
use Quidco\DbSampler\Cleaner\RowCleaner;
use Quidco\DbSampler\Collection\TableCollection;
use Quidco\DbSampler\Collection\ViewCollection;
use Quidco\DbSampler\ReferenceStore;
use Quidco\DbSampler\Sampler\Sampler;
use Quidco\DbSampler\SamplerMap\SamplerMap;
use Quidco\DbSampler\Writer\Writer;

/**
 * Migrator class to handle all migrations in a set
 */
class Migrator
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Connection
     */
    private $sourceConnection;

    /**
     * @var Connection
     */
    private $destConnection;

    /**
     * @var ReferenceStore
     */
    private $referenceStore;


    public function __construct(
        Connection $sourceConnection,
        Connection $destConnection,
        LoggerInterface $logger
    )
    {
        $this->sourceConnection = $sourceConnection;
        $this->destConnection = $destConnection;
        $this->logger = $logger;
        $this->referenceStore = new ReferenceStore();
    }

    /**
     * Perform the configured migrations
     *
     * @throws \Exception Rethrows any exceptions after logging
     */
    public function execute(string $setName, TableCollection $tableCollection, ViewCollection $viewCollection): void
    {

        foreach ($tableCollection->getTables() as $table => $migrationSpec) {
            // @todo: it'd probably be better to have a proper `migrationspec` config object
            // rather than relying on properties being present in the json / stdClass object

            $sampler = $this->buildTableSampler($migrationSpec, $table);
            $writer = new Writer($migrationSpec, $this->destConnection);
            $cleaner = new RowCleaner($migrationSpec, $table);

            try {
                $this->ensureEmptyTargetTable($table, $this->sourceConnection, $this->destConnection);
                $rows = $sampler->execute();

                foreach ($rows as $row) {
                    $writer->write($table, $cleaner->cleanRow($row));
                }
                $writer->postWrite();

                $this->logger->info("$setName: migrated '$table' with '" . $sampler->getName() . "': " . \count($rows) . " rows");
            } catch (\Exception $e) {
                $this->logger->error(
                    "$setName: failed to migrate '$table' with '" . $sampler->getName() . "': " . $e->getMessage()
                );
                throw $e;
            }
        }

        foreach ($viewCollection->getViews() as $view) {
            $this->migrateView($view, $setName);
        }

        $this->migrateTableTriggers($setName, $tableCollection);
    }


    /**
     * Ensure that the specified table is present in the destination DB as an empty copy of the source
     *
     * @param string $table Table name
     * @param Connection $sourceConnection Originating DB connection
     * @param Connection $destConnection Target DB connection
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
            $schemaSql = 'SELECT sql FROM sqlite_master WHERE type="table" AND tbl_name=' . $sourceConnection->quoteIdentifier($table);
            $createSql = $sourceConnection->query($schemaSql)->fetchColumn();
        } else {
            throw new \RuntimeException(__METHOD__ . " not implemented for $driverName yet");
        }

        $destConnection->exec($createSql);
    }

    /**
     * Ensure that all table triggers from source are recreated in the destination
     *
     * @return void
     * @throws \RuntimeException If DB type not supported
     * @throws \Doctrine\DBAL\DBALException If target trigger cannot be recreated
     */
    private function migrateTableTriggers(string $setName, TableCollection $tableCollection): void
    {
        foreach ($tableCollection->getTables($this->referenceStore) as $table => $sampler) {
            try {
                $triggerSql = $this->generateTableTriggerSql($table, $this->sourceConnection);
                foreach ($triggerSql as $sql) {
                    $this->destConnection->exec($sql);
                }
                if (count($triggerSql)) {
                    $this->logger->info("$setName: Migrated " . count($triggerSql) . " trigger(s) on $table");
                }
            } catch (\Exception $e) {
                $this->logger->error(
                    "$setName: failed to migrate '$table' with '" . $sampler->getName() . "': " . $e->getMessage()
                );
                throw $e;
            }
        }
    }

    /**
     * Regenerate the SQL to create any triggers from the table
     *
     * @param string $table Table name
     * @param Connection $dbConnection Originating DB connection
     *
     * @return array
     * @throws \RuntimeException If DB type not supported
     */
    private function generateTableTriggerSql($table, Connection $dbConnection)
    {
        $driverName = $dbConnection->getDriver()->getName();
        $triggerSql = [];
        if ($driverName === 'pdo_mysql') {
            $triggers = $dbConnection->fetchAll('SHOW TRIGGERS WHERE `Table`=' . $dbConnection->quote($table));
            if ($triggers && count($triggers) > 0) {
                foreach ($triggers as $trigger) {
                    $triggerSql[] = 'CREATE TRIGGER ' . $trigger['Trigger'] . ' ' . $trigger['Timing'] . ' ' . $trigger['Event'] .
                        ' ON ' . $dbConnection->quoteIdentifier($trigger['Table']) . ' FOR EACH ROW ' . PHP_EOL . $trigger['Statement'] . '; ';
                }
            }
        } elseif ($driverName === 'pdo_sqlite') {
            $schemaSql = "select sql from sqlite_master where type = 'trigger' AND tbl_name=" . $dbConnection->quote($table);
            $triggers = $dbConnection->fetchAll($schemaSql);
            if ($triggers && count($triggers) > 0) {
                foreach ($triggers as $trigger) {
                    $triggerSql[] = $trigger['sql'];
                }
            }
        } else {
            throw new \RuntimeException(__METHOD__ . " not implemented for $driverName yet");
        }

        return $triggerSql;
    }

    /**
     * Migrate a view from source to dest DB
     *
     * @param string $view Name of view to migrate
     * @param string $setName Name of migration set being executed
     *
     * @throws \Doctrine\DBAL\DBALException If view cannot be read
     * @throws \RuntimeException For DB types where this is unsupported
     */
    protected function migrateView(string $view, string $setName): void
    {
        $sourceConnection = $this->sourceConnection;
        $destConnection = $this->destConnection;

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

        $this->logger->info("$setName: migrated view '$view'");
    }

    /**
     * Build a Sampler object from configuration
     *
     * @param \stdClass $migrationSpec
     * @param string $tableName
     */
    private function buildTableSampler(\stdClass $migrationSpec, string $tableName): Sampler
    {
        $sampler = null;

        // @todo: $migrationSpec should be an object with a getSampler() method
        $samplerType = strtolower($migrationSpec->sampler);
        if (array_key_exists($samplerType, SamplerMap::MAP)) {
            $samplerClass = SamplerMap::MAP[$samplerType];
            $sampler = new $samplerClass(
                $migrationSpec,
                $this->referenceStore,
                $this->sourceConnection,
                $tableName
            );
        } else {
            throw new \RuntimeException("Unrecognised sampler type '$samplerType' required");
        }

        return $sampler;
    }
}
