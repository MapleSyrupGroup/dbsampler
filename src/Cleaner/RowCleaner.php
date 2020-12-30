<?php

namespace Quidco\DbSampler\Cleaner;

class RowCleaner
{
    /**
     * @var \stdClass
     */
    private $migrationSpec;

    /**
     * @var string
     */
    private $tableName;

    private $customCleaners = [];

    public function __construct(\stdClass $migrationSpec, string $tableName)
    {
        $this->migrationSpec = $migrationSpec;
        $this->tableName = $tableName;
    }

    public function cleanRow(array $row): array
    {
        if (!$this->migrationSpec->cleanFields) {
            return $row;
        }

        $cleanFields = \json_decode(\json_encode($this->migrationSpec->cleanFields), true);

        \array_walk($row, function (&$item, $key) use ($cleanFields) {
            if (\array_key_exists($key, $cleanFields)) {
                $cleanerConfig = CleanerConfig::fromString($cleanFields[$key]);

                if (\array_key_exists($cleanerConfig->getName(), $this->customCleaners)) {
                    $cleaner = $this->customCleaners[$cleanerConfig->getName()];
                } else {
                    $cleaner = FieldCleanerFactory::getCleaner($cleanerConfig);
                }

                $item = $cleaner->clean($cleanerConfig->getParameters(), $item);
            }
        });

        return $row;
    }

    public function registerCleaner(FieldCleaner $fieldCleaner, string $alias)
    {
        $this->customCleaners[$alias] = $fieldCleaner;
    }
}