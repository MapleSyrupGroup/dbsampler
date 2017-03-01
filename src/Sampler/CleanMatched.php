<?php
namespace Quidco\DbSampler\Sampler;

use Quidco\DbSampler\RowCleaner;

/**
 * Sampler that selects specified rows and cleans them
 */
class CleanMatched extends Matched
{
    /**
     * Configured RowCleaner instance
     *
     * @var RowCleaner
     */
    protected $rowCleaner;

    /**
     * Accept configuration as provided in a .db.json file
     *
     * @param \stdClass $config Configuration stanza, decoded to object
     *
     * @return void
     * @throws \RuntimeException If config is invalid
     * @inheritdoc
     */
    public function loadConfig($config)
    {
        $cleanSpec = (array)$config->cleanFields;
        $this->rowCleaner = RowCleaner::createFromSpecification($cleanSpec);
        parent::loadConfig($config);
    }

    /**
     * Return cleaned rows
     *
     * @return \array[]
     */
    public function getRows()
    {
        $rows = parent::getRows();

        foreach ($rows as &$row) {
            $this->rowCleaner->cleanRow($row);
        }

        return $rows;
    }
}
