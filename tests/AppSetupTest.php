<?php

namespace Quidco\DbSampler\Tests;

use Quidco\DbSampler\App;

/**
 * Class AppSetupTest
 */
class AppSetupTest extends SqliteBasedTestCase
{
    /**
     * Run the example migration
     *
     * @return void
     */
    public function testSampleMigration(): void
    {
        $app = new App();
        $this->assertInstanceOf(App::class, $app);
        $app->loadDatabaseConfigFile($this->fixturesDir . '/small_sqlite_migration.json');
        $this->assertSame(['small-sqlite-test'], $app->getConfiguredMigrationNames());
        $app->loadCredentialsFile($this->fixturesDir . '/sqlite-credentials.json');
        $app->performMigrationSet('small-sqlite-test');

        // Test copies over only apples, pears, and the baskets containing them
        $this->assertSame('2', $this->destConnection->query('SELECT COUNT(*) FROM fruits')->fetchColumn());
        $this->assertSame('2', $this->destConnection->query('SELECT COUNT(*) FROM fruit_x_basket')->fetchColumn());
        $this->assertSame('3', $this->sourceConnection->query('SELECT COUNT(*) FROM baskets')->fetchColumn());
        $this->assertSame('2', $this->destConnection->query('SELECT COUNT(*) FROM baskets')->fetchColumn());
    }

    /**
     * Check that sqlite credential files handle missing directory field correctly
     *
     * @return void
     */
    public function testSqliteCredentialMissingDirectoryHandling(): void
    {
        $app = new App();
        $app->loadCredentialsFile($this->fixturesDir . '/sqlite-credentials-no-dir.json');
        $app->loadDatabaseConfigFile($this->fixturesDir . '/small_sqlite_migration.json');
        $this->expectException(\RuntimeException::class);
        $app->createDestConnectionByDbName('small-sqlite-source'); // directory tested at connection time now
    }

    /**
     * Check that sqlite credential files handle relative directory field correctly
     *
     * @return void
     */
    public function testSqliteCredentialRelativeDirectoryHandling(): void
    {
        $app = new App();
        $app->loadCredentialsFile($this->fixturesDir . '/sqlite-credentials-relative-dir.json');
        $app->loadDatabaseConfigFile($this->fixturesDir . '/small_sqlite_migration.json');
        $app->createDestConnectionByDbName('small-sqlite-source');
        // resolution of dirs now happens later

        $configuredPath = $app['db.credentials']->directory;
        $this->assertTrue(is_dir($configuredPath), "Sqlite relative directory path should resolve");
        $this->assertRegExp('#/tests/#', $configuredPath, 'Sqlite relative directory must resolve to full path');
    }

    /**
     * Check that sqlite credential files handle source, dest DBs correctly
     *
     * @return void
     */
    public function testSqliteCredentialSourceDestDirectoryHandling(): void
    {
        $app = new App();
        $app->loadCredentialsFile($this->fixturesDir . '/sqlite-credentials-source-dest.json');
        $app->loadDatabaseConfigFile($this->fixturesDir . '/small_sqlite_migration.json');
        $destConn = $app->createDestConnectionByDbName('small-sqlite-source');
        $this->assertInstanceOf(\Doctrine\DBAL\Connection::class, $destConn);
    }
}
