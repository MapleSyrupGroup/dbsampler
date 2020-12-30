<?php

namespace Quidco\DbSampler\Cleaner;

use Quidco\DbSampler\FieldCleanerProvider;

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

                $cleaner = FieldCleanerFactory::getCleaner($cleanerConfig);

                $item = $cleaner->clean($cleanerConfig->getParameters(), $item);
            }
        });

        return $row;
    }
}
