<?php
namespace Quidco\DbSampler;

use Monolog\Handler\ErrorLogHandler;
use Monolog\Logger;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Quidco\DbSampler\Configuration\MigrationConfigurationCollection;

/**
 * Console Runner for Quidco\DbSampler\App
 */
class ConsoleRunner implements LoggerAwareInterface
{

    /**
     * Logger instance to pass to app
     *
     * @var Logger
     */
    protected $logger;

    /**
     * Whether to show CLI help
     *
     * @var boolean
     */
    protected $helpNeeded = false;

    /**
     * Path of credentials file
     *
     * @var string
     */
    protected $credentialsFilePath;

    /**
     * List of databases to migrate
     *
     * @var array
     */
    protected $databases = [];

    /**
     * List of migration files to load
     *
     * @var array
     */
    protected $migrationFilePaths = [];

    /**
     * Error messages relating to command line, if any
     *
     * @var string|null
     */
    protected $paramError;


    /**
     * Read command-line options, configure and run app
     *
     * @return void
     */
    public function run()
    {
        try {
            $this->parseOpts();
        } catch (\InvalidArgumentException $e) {
            $this->paramError = $e->getMessage();
        }

        if (!$this->credentialsFilePath) {
            $this->helpNeeded = true;
            $this->paramError = 'Credentials path missing';
        }

        if (!$this->migrationFilePaths) {
            $this->helpNeeded = true;
            $this->paramError = 'Migration file path(s) missing';
        }

        if ($this->helpNeeded) {
            $exitCode = 0;
            if ($this->paramError) {
                print("\n" . $this->paramError . "\n");
                $exitCode = -1;
            }
            $this->showHelp();
            exit($exitCode);
        }

        $app = new App(
            MigrationConfigurationCollection::fromFilePaths($this->migrationFilePaths)
        );

        $app->setLogger($this->getLogger());

        try {
            $app->loadCredentialsFile($this->credentialsFilePath);
        } catch (\RuntimeException $e) {
            print("Credentials file '{$this->credentialsFilePath}' is invalid " . $e->getMessage());
        }


        if (!$this->databases) {
            $this->databases = $app->getConfiguredMigrationNames();
        }

        foreach ($this->databases as $database) {
            try {
                $app->performMigrationSet($database);
            } catch (\RuntimeException $e) {
                print("Migration '$database' failed " . $e->getMessage());
            }
        }
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
     * Get configured logger, or a default one if unconfigured
     *
     * @return Logger
     */
    public function getLogger()
    {
        if (!$this->logger) {
            $logger = new Logger('dbsampler');
            $logger->pushHandler(new ErrorLogHandler());
            $this->setLogger($logger);
        }

        return $this->logger;
    }

    /**
     * Parse command-line options
     *
     * @return void
     * @throws \InvalidArgumentException If options are invalid
     */
    private function parseOpts()
    {
        /** @noinspection PhpParamsInspection */ // PHPStorm having issues
        $opts = getopt('c:m:d:h', ['credentials:', 'migrations:', 'databases:', 'help']);
        foreach ($opts as $k => $v) {
            switch ($k) {
                case 'h':
                case 'help':
                    $this->helpNeeded = true;
                    break;

                case 'c':
                case 'credentials':
                    if (is_array($v)) {
                        throw new \InvalidArgumentException('Exactly one credentials file is required');
                    }
                    $this->credentialsFilePath = $v;
                    break;

                case 'd':
                case 'databases':
                    if (is_array($v)) {
                        $this->databases = $v;
                    } else {
                        $this->databases = explode(',', $v);
                    }
                    break;

                case 'm':
                case 'migrations':
                    if (is_array($v)) {
                        $this->migrationFilePaths = $v;
                    } else {
                        $this->migrationFilePaths = [$v];
                    }
                    break;

                default:
                    $this->helpNeeded = true;
            }
        }
    }

    /**
     * Dump help to screen
     *
     * @return void
     */
    protected function showHelp()
    {
        global $argv;
        $scriptName = basename($argv[0]);
        print("\n$scriptName: Extract values from a database for use as fixtures\n\n");
        print("-c --credentials FILE     Required    Get database credentials from FILE\n");
        print("-m --migrations  FILE     Required    Get migration specifications from FILE\n");
        print("                                        Can be specified multiple times, or be a directory\n");
        print("                                        If a directory, all .json files are parsed\n");
        print(
            '-d --databases   STRING   Optional    Only generate migrations for specified DBs ' .
            "(name field in migration file)\n"
        );
        print("                                        By default, all migrations specified with -m are executed\n");

        print("-h --help                 Optional    Shows this help\n");

        print("\nFor more details, see README.md\n\n");
    }
}
