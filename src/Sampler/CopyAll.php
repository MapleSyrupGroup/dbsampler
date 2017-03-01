<?php
namespace Quidco\DbSampler\Sampler;

use Quidco\DbSampler\BaseSampler;

class CopyAll extends BaseSampler
{

    public function getName()
    {
        return 'CopyAll';
    }

    public function getRows()
    {
        $query = $this->sourceConnection->createQueryBuilder()->select('*')->from($this->tableName)->execute();

        return $query->fetchAll();
    }
}
