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
        $migrator = new Migrator($name);
        $migrator->setLogger($this->getLogger());
        $migrator->setDatabaseConnectionFactory($this);

        $migrationConfigProcessor = new MigrationConfigProcessor();
        $migrationConfigProcessor->configureMigratorFromConfig($migrator, $this["db.migrations.$name"]);

        $migrator->execute();
    }

    /**
     * Create a connection class for a given DB name. Other credentials (host, password etc) must already be known
     *
     * @param string $name Database name
     *
     * @return Connection
     * @throws \UnexpectedValueException If configuration is invalid
     * @throws \Doctrine\DBAL\DBALException If connection cannot be made
     */
    public function createConnectionByDbName($name)
    {
        if (!isset($this["db.connections.$name"])) {
            switch (isset($this['db.credentials']->driver) ? $this['db.credentials']->driver : 'pdo_mysql') {
                case 'pdo_sqlite':
                    if (empty($this['db.credentials']->directory)) {
                        throw new \UnexpectedValueException('Directory is required in sqlite configuration');
                    }
                    $params = [
                        'driver' => 'pdo_sqlite',
                        'path' => rtrim($this['db.credentials']->directory, DIRECTORY_SEPARATOR)
                            . '/' . $name . '.sqlite',
                    ];
                    break;

                case 'pdo_mysql':
                    $params = [
                        'driver' => 'pdo_mysql',
                        'user' => $this['db.credentials']->dbUser,
                        'password' => $this['db.credentials']->dbPassword,
                        'host' => $this['db.credentials']->dbHost,
                        'dbname' => $name,
                    ];
                    break;

                default:
                    throw new \UnexpectedValueException(
                        'Driver type "' . $this['db.credentials']->driver . '" not supported yet'
                    );
            }

            $this["db.connections.$name"] = DriverManager::getConnection($params);
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
