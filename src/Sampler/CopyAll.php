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
        $query = $this->sourceConnection->createQueryBuilder()->select('*')->from($this->tableName);

        if ($this->limit) {
            $query->setMaxResults($this->limit);
        }

        return $query->execute()->fetchAll();
    }
}
