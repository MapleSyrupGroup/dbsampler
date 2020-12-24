<?php


namespace Quidco\DbSampler\Configuration;


class MigrationConfigurationCollection
{
    /**
     * @var array
     */
    private $configs;

    private function __construct(array $configs)
    {
        $this->configs = $configs;
    }

    public static function fromFilePaths(array $filepaths): self
    {
        $configs = [];

        foreach ($filepaths as $migrationFilePath) {
            try {
                if (is_dir($migrationFilePath)) {
                    $migrationFiles = glob(rtrim($migrationFilePath, '/') . '/*.json');
                } else {
                    $migrationFiles = [$migrationFilePath];
                }
                foreach ($migrationFiles as $file) {
                    $config = MigrationConfiguration::fromJson(file_get_contents($file));

                    $configs[$config->getName()] = $config;

                }
            } catch (\RuntimeException $e) {
                print("Migration file '$migrationFilePath' is invalid " . $e->getMessage());
            }
        }

        return new self($configs);
    }

    public function get(string $configName): MigrationConfiguration
    {
        return $this->configs[$configName];
    }

    public function listConfigurations(): array
    {
        return \array_keys($this->configs);
    }
}
