<?php

namespace Quidco\DbSampler\Sampler;

use Quidco\DbSampler\BaseSampler;
use Quidco\DbSampler\RowCleaner;

class CleanAll extends BaseSampler
{
    /**
     * Configured RowCleaner instance
     *
     * @var RowCleaner
     */
    protected $rowCleaner;

    /**
     * Return a unique name for this sampler for informational purposes
     *
     * @return string
     * @inheritdoc
     */
    public function getName()
    {
        return 'CleanAll';
    }

    /**
     * Return all rows that this sampler would copy
     *
     * @inheritdoc
     */
    public function getRows(): array
    {
        $cleanSpec = (array)$this->config->cleanFields;
        $this->rowCleaner = RowCleaner::createFromSpecification($cleanSpec);

        $query = $this->sourceConnection->createQueryBuilder()->select('*')->from($this->tableName);

        if ($this->limit) {
            $query->setMaxResults($this->limit);
        }

        $rows = $query->execute()->fetchAll();

        foreach ($rows as &$row) {
            $this->rowCleaner->cleanRow($row);
        }

        return $rows;
    }
}
