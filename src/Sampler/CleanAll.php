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
     * Accept configuration as provided in a .db.json file
     *
     * @param \stdClass $config Configuration stanza, decoded to object
     *
     * @return void
     * @throws \RuntimeException If invalid cleaner specified
     * @inheritdoc
     *
     * eg
     * "qco_networks": {
     * "sampler": "cleanAll",
     * "cleanFields": {
     * "contact": "fakeFullName",
     * "email": "fakeEmail"
     * }
     * },
     */
    public function loadConfig($config)
    {
        $cleanSpec = (array)$config->cleanFields;
        $this->rowCleaner = RowCleaner::createFromSpecification($cleanSpec);
    }

    /**
     * Return all rows that this sampler would copy
     *
     * @return array[]
     * @inheritdoc
     */
    public function getRows()
    {
        $query = $this->sourceConnection->createQueryBuilder()->select('*')->from($this->tableName)->execute();

        $rows = $query->fetchAll();

        foreach ($rows as &$row) {
            $this->rowCleaner->cleanRow($row);
        }

        return $rows;
    }
}
