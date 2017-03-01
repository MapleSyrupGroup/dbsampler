<?php

namespace Quidco\DbSampler\Tests;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;

abstract class SqliteBasedTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @var string
     */
    protected $fixturesDir;
    /**
     * @var Connection
     */
    protected $destConnection;
    /**
     * @var Connection
     */
    protected $sourceConnection;

    /**
     * Create config file and connections
     *
     * @return void
     */
    protected function setUp()
    {
        $this->fixturesDir = __DIR__ . '/fixtures';
        $sqliteConfig = ['driver' => 'pdo_sqlite', 'directory' => $this->fixturesDir . '/sqlite-dbs'];
        file_put_contents(
            $this->fixturesDir . '/sqlite-credentials.json',
            json_encode($sqliteConfig, JSON_PRETTY_PRINT)
        );

        $this->setupDbConnections();
        $this->populateSqliteDb();
        parent::setUp();
    }

    /**
     * Remove temporary files
     *
     * @return void
     */
    protected function tearDown()
    {
        parent::tearDown();
        unlink($this->fixturesDir . '/sqlite-credentials.json');
    }

    /**
     * Create a sqlite DB with known content
     *
     * @return void
     */
    protected function populateSqliteDb()
    {

        $sql = explode(';', file_get_contents($this->fixturesDir . '/small-source.sql'));

        foreach ($sql as $command) {
            if (trim($command)) {
                $this->sourceConnection->exec($command);
            }
        }
    }

    /**
     * Create DB handles for source & dest DBs
     *
     * @return void
     */
    protected function setupDbConnections()
    {
        $this->sourceConnection = DriverManager::getConnection(
            [
                'driver' => 'pdo_sqlite',
                'path' => $this->fixturesDir . '/sqlite-dbs/small-source.sqlite',
            ]
        );

        $this->destConnection = DriverManager::getConnection(
            [
                'driver' => 'pdo_sqlite',
                'path' => $this->fixturesDir . '/sqlite-dbs/small-dest.sqlite',
            ]
        );
    }
}
