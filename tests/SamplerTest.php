<?php

namespace Quidco\DbSampler\Tests;

use Quidco\DbSampler\ReferenceStore;
use Quidco\DbSampler\Sampler\AllRows;
use Quidco\DbSampler\Sampler\None;
use Quidco\DbSampler\Sampler\MatchedRows;

class SamplerTest extends SqliteBasedTestCase
{
    public function testEmptySampler(): void
    {
        $sampler = new None((object)[]);
        $this->assertSame([], $sampler->getRows());
    }

    public function testCopyAllSampler(): void
    {
        $sampler = new AllRows((object)[]);
        $sampler->setTableName('fruits');
        $sampler->setSourceConnection($this->sourceConnection);
        $this->assertCount(4, $sampler->getRows());
    }

    public function testCopyAllWithReferenceStore(): void
    {
        $sampler = new AllRows((object)['remember' => ['id' => 'fruit_ids']]);
        $sampler->setTableName('fruits');
        $sampler->setSourceConnection($this->sourceConnection);
        $sampler->setDestConnection($this->destConnection);
        $referenceStore = new ReferenceStore();
        $sampler->setReferenceStore($referenceStore);

        $sampler->execute();

        $this->assertCount(4, $referenceStore->getReferencesByName('fruit_ids'));
    }

    private function generateMatched($config)
    {
        $sampler = new MatchedRows($config);
        $sampler->setTableName('fruit_x_basket');
        $sampler->setSourceConnection($this->sourceConnection);
        $sampler->setDestConnection($this->destConnection);
        return $sampler;
    }

    public function testMatchedWithWhereClause(): void
    {
        $config = [
            'constraints' => ['fruit_id' => 1],
            'where' => ['basket_id > 1']
        ];
        $sampler = $this->generateMatched((object)$config);
        $sampler->execute();

        $this->assertCount(2, $sampler->getRows());
    }

    public function testMatchedWhereNoConstraints(): void
    {
        $config = [
            'where' => ['basket_id > 1']
        ];
        $sampler = $this->generateMatched((object)$config);
        $sampler->execute();
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testMatchedNoConfigThrows(): void
    {
        $sampler = $this->generateMatched((object)[]);
        $sampler->execute();
    }
}
