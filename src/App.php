<?php

namespace Quidco\DbSampler;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Pimple\Container;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Dependency container / app for DbSampler
 */
class App extends Container implements DatabaseConnectionFactoryInterface, LoggerAwareInterface
{
    /**
     * PSR logger
     *
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * List of names from migration configs
     *
     * @var string[]
     */
    protected $configuredMigrationNames = [];

    /**
     * Load common DB credentials file
     *
     * @param string $filename Path to credentials.json
     *
     * @return void
     * @throws \RuntimeException If file cannot be parsed
     */
    public function loadCredentialsFile($filename)
    {
        $credentials = json_decode(file_get_contents($filename));
        if (!$credentials) {
            throw new \RuntimeException("Credentials file '$filename' could not be read");
        }

        $this['db.credentials'] = $credentials;
        $this['db.credentialsFile'] = $filename;
    }

    /**
     * Load migration specification for a single database
     *
     * @param string $filename Path to specification file
     *
     * @return void
     * @throws \RuntimeException If config is invalid
     */
    public function loadDatabaseConfigFile($filename)
    {
        $config = json_decode(file_get_contents($filename));
        $name = $config->name;
        if (!$name) {
            throw new \RuntimeException("Migration file '$filename' has no name field");
        }

        $this->configuredMigrationNames[] = $name;
        $this["db.migrations.$name"] = $config;
    }

    /**
     * Perform migrations from a named set
     *
     * @param string $name Migration name as specified in a .db.json migration file
     *
     * @return void
     * @throws \RuntimeException If config is invalid
     */
    public function performMigrationSet($name)
    {
        $migrationConfigProcessor = new MigrationConfigProcessor();

        $configuration = $this["db.migrations.$name"];

        $sourceConnection = $this->createSourceConnectionByDbName($configuration->sourceDb);
        $destConnection = $this->createDestConnectionByDbName($configuration->destDb);

        $migrator = new Migrator($sourceConnection, $destConnection, $this->getLogger());
        $migrationConfigProcessor->configureMigratorFromConfig($migrator, $configuration);

        $migrator->execute($name);
    }

    /**
     * Create source connection for a given DB name. Other credentials (host, password etc) must already be known
     *
     * @param string $name Database name
     *
     * @return Connection
     * @throws \UnexpectedValueException If configuration is invalid
     * @throws \Doctrine\DBAL\DBALException If connection cannot be made
     */
    public function createSourceConnectionByDbName($name)
    {
        return $this->createConnectionByDbName($name, self::CONNECTION_SOURCE);
    }

    /**
     * Create dest connection for a given DB name. Other credentials (host, password etc) must already be known
     *
     * @param string $name Database name
     *
     * @return Connection
     * @throws \UnexpectedValueException If configuration is invalid
     * @throws \Doctrine\DBAL\DBALException If connection cannot be made
     */
    public function createDestConnectionByDbName($name)
    {
        return $this->createConnectionByDbName($name, self::CONNECTION_DEST);
    }

    /**
     * Create connection object for DB name / direction. Other credentials (host, password etc) must already be known
     *
     * @param string $name Database name
     * @param string $direction Determines whether 'source' or 'dest' credentials used, must be one of those values
     *
     * @return Connection
     * @throws \UnexpectedValueException If configuration is invalid
     * @throws \Doctrine\DBAL\DBALException If connection cannot be made
     */
    protected function createConnectionByDbName($name, $direction = self::CONNECTION_SOURCE)
    {
        if (!isset($this["db.connections.$name"])) {
            $dbcredentials = isset($this["db.credentials"]->$direction) ?
                $this["db.credentials"]->$direction :
                $this["db.credentials"];

            switch (isset($dbcredentials->driver) ? $dbcredentials->driver : 'pdo_mysql') {
                case 'pdo_sqlite':
                    if (empty($dbcredentials->directory)) {
                        throw new \UnexpectedValueException('Directory is required in sqlite configuration');
                    } else {
                        if (strpos($dbcredentials->directory, DIRECTORY_SEPARATOR) !== 0) {
                            // not an absolute path, treat as relative to sqlite file location
                            $dbcredentials->directory = realpath(
                                dirname($this['db.credentialsFile']) . DIRECTORY_SEPARATOR . $dbcredentials->directory
                            );
                        }
                    }
                    $params = [
                        'driver' => 'pdo_sqlite',
                        'path' => rtrim($dbcredentials->directory, DIRECTORY_SEPARATOR)
                            . '/' . $name . '.sqlite',
                    ];
                    break;

                case 'pdo_mysql':
                    $params = [
                        'driver' => 'pdo_mysql',
                        'user' => $dbcredentials->dbUser,
                        'password' => $dbcredentials->dbPassword,
                        'host' => $dbcredentials->dbHost,
                        'dbname' => $name,
                    ];

                    if (!empty($dbcredentials->dbPort)) {
                        $params['port'] = $dbcredentials->dbPort;
                    }
                    break;

                default:
                    throw new \UnexpectedValueException(
                        'Driver type "' . $dbcredentials->driver . '" not supported yet'
                    );
            }

            $this["db.connections.$name"] = DriverManager::getConnection($params);

            if (isset($dbcredentials->initialSql)) {
                foreach ($dbcredentials->initialSql as $command) {
                    $this["db.connections.$name"]->exec($command);
                }
            }
        }

        return $this["db.connections.$name"];
    }

    /**
     * Sets a logger instance on the object.
     *
     * @param LoggerInterface $logger PSR logger
     *
     * @return void
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Get configured logger or safe null logger
     *
     * @return LoggerInterface
     */
    protected function getLogger()
    {
        return $this->logger ?: new NullLogger();
    }

    /**
     * Get the list of loaded migration names
     *
     * @return string[]
     */
    public function getConfiguredMigrationNames()
    {
        return $this->configuredMigrationNames;
    }
}
