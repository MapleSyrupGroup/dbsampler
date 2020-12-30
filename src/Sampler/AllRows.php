<?php

namespace Quidco\DbSampler\Sampler;

use Quidco\DbSampler\BaseSampler;

class AllRows extends BaseSampler implements Sampler
{
    public function getName(): string
    {
        return 'All';
    }

    public function getRows(): array
    {
        $query = $this->sourceConnection->createQueryBuilder()->select('*')->from($this->tableName);

        if ($this->limit) {
            $query->setMaxResults($this->limit);
        }

        return $query->execute()->fetchAll();
    }
}
