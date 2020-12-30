<?php

namespace Quidco\DbSampler\Tests;

use Quidco\DbSampler\ReferenceStore;
use Quidco\DbSampler\Sampler\AllRows;
use Quidco\DbSampler\Sampler\None;
use Quidco\DbSampler\Sampler\MatchedRows;
use Quidco\DbSampler\SamplerInterface;

class SamplerTest extends SqliteBasedTestCase
{
    public function testEmptySampler(): void
    {
        $sampler = new None(
            (object)[],
            new ReferenceStore(),
            $this->sourceConnection
        );
        $this->assertSame([], $sampler->getRows());
    }

    public function testCopyAllSampler(): void
    {
        $sampler = new AllRows(
            (object)[],
            new ReferenceStore(),
            $this->sourceConnection
        );
        $sampler->setTableName('fruits');
        $this->assertCount(4, $sampler->getRows());
    }

    public function testCopyAllWithReferenceStore(): void
    {
        $referenceStore = new ReferenceStore();

        $sampler = new AllRows(
            (object)['remember' => ['id' => 'fruit_ids']],
            $referenceStore,
            $this->sourceConnection
        );
        $sampler->setTableName('fruits');

        $sampler->execute();

        $this->assertCount(4, $referenceStore->getReferencesByName('fruit_ids'));
    }

    private function generateMatched($config): SamplerInterface
    {
        $sampler = new MatchedRows(
            $config,
            new ReferenceStore(),
            $this->sourceConnection
        );
        $sampler->setTableName('fruit_x_basket');
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
