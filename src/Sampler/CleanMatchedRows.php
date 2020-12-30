<?php
namespace Quidco\DbSampler\Sampler;

use Quidco\DbSampler\RowCleaner;

/**
 * Sampler that selects specified rows and cleans them
 */
class CleanMatchedRows extends MatchedRows
{
    /**
     * Configured RowCleaner instance
     *
     * @var RowCleaner
     */
    protected $rowCleaner;

    /**
     * Return cleaned rows
     *
     * @return \array[]
     */
    public function getRows()
    {
        if (!isset($this->config->cleanFields)) {
            throw new \RuntimeException("cleanFields missing for {$this->config->sampler}");
        }
        $cleanSpec = (array)$this->config->cleanFields;
        $this->rowCleaner = RowCleaner::createFromSpecification($cleanSpec);

        $rows = parent::getRows();

        foreach ($rows as &$row) {
            $this->rowCleaner->cleanRow($row);
        }

        return $rows;
    }
}
