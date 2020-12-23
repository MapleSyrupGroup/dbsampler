<?php
namespace Quidco\DbSampler;

use Quidco\DbSampler\Migrator\Migrator;
use Quidco\DbSampler\Sampler\CleanAll;
use Quidco\DbSampler\Sampler\CleanMatched;
use Quidco\DbSampler\Sampler\CopyAll;
use Quidco\DbSampler\Sampler\CopyEmpty;
use Quidco\DbSampler\Sampler\Matched;
use Quidco\DbSampler\Sampler\NewestById;

/**
 * Configure a Migrator from file config (so that it doesn't need to be self-aware)
 */
class MigrationConfigProcessor
{

    /**
     * Map of SamplerInterface implementations by name
     *
     * Note that name is lowercase so that config does not have to be case sensitive
     *
     * @var string[]
     */
    protected $samplerMap = [
        'all' => CopyAll::class,
        'empty' => CopyEmpty::class,
        'matched' => Matched::class,
        'newestbyid' => NewestById::class,
        'cleanall' => CleanAll::class,
        'cleanmatched' => CleanMatched::class,
    ];


    /**
     * @var ReferenceStore
     */
    protected $referenceStore;

    /**
     * Create a MigrationConfigProcessor with empty ReferenceStore
     */
    public function __construct()
    {
        $this->referenceStore = new ReferenceStore();
    }

    /**
     * Apply the settings in the config file to the provided migrator
     *
     * @param Migrator  $migrator      Migrator to configure
     * @param \stdClass $configuration Configuration to apply
     *
     * @return void
     * @throws \RuntimeException If any configuration fails
     */
    public function configureMigratorFromConfig(Migrator $migrator, \stdClass $configuration)
    {
        if (!strcasecmp(trim($configuration->sourceDb), trim($configuration->destDb))) {
            throw new \RuntimeException("Source and dest DBs must not match for '{$configuration->name}'");
        }

        $tables = (array)$configuration->tables;
        $migrator->clearTableMigrations();
        foreach ($tables as $table => $migrationSpec) {
            $migrator->addTableSampler($table, $this->buildTableSampler($migrationSpec));
        }

        if (isset($configuration->views)) {
            $views = (array)$configuration->views;
            foreach ($views as $view) {
                $migrator->addViewToMigrate($view);
            }
        }
    }

    /**
     * Build a SamplerInterface object from configuration
     *
     * @throws \UnexpectedValueException If bad object created - should be impossible
     * @throws \RuntimeException On invalid specification
     */
    private function buildTableSampler(\stdClass $migrationSpec): SamplerInterface
    {
        $sampler = null;

        $samplerType = strtolower($migrationSpec->sampler);
        if (array_key_exists($samplerType, $this->samplerMap)) {
            $samplerClass = $this->samplerMap[$samplerType];
            $sampler = new $samplerClass;
            if (!$sampler instanceof SamplerInterface) {
                throw new \UnexpectedValueException('Invalid sampler created');
            }
            /** @var SamplerInterface $sampler */
            $sampler->loadConfig($migrationSpec);
            $sampler->setReferenceStore($this->referenceStore);
        } else {
            throw new \RuntimeException("Unrecognised sampler type '$samplerType' required");
        }

        return $sampler;
    }
}
