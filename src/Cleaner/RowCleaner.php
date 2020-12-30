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

        $fieldCleanerProvider = new FieldCleanerProvider();

        \array_walk($row, function (&$item, $key) use ($fieldCleanerProvider, $cleanFields) {
            // @todo there are no tests that match this \array_key_exists condition!
            if (\array_key_exists($key, $cleanFields)) {
                $item = $fieldCleanerProvider->getCleanerByDescription($cleanFields[$key])($item);
            }
        });

        return $row;
    }
}

