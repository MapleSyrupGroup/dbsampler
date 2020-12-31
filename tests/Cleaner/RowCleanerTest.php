<?php

namespace Quidco\DbSampler\Tests\Cleaner;

use PHPUnit\Framework\TestCase;
use Quidco\DbSampler\Cleaner\FieldCleaner;
use Quidco\DbSampler\Cleaner\RowCleaner;

class RowCleanerTest extends TestCase
{
    public function testARowWithoutCleanerConfigIsNotModified(): void
    {
        $cleaner = new RowCleaner((object)[], 'test-table');

        $row = [
            'id' => 12345,
            'email_address' => 'test@example.com'
        ];

        $this->assertEquals($row, $cleaner->cleanRow($row));
    }

    public function testARowIsModified(): void
    {
        $cleaner = new RowCleaner((object)[
            'cleanFields' => [
                'email_address' => 'fakeemail'
            ]
        ]);

        $row = [
            'id' => 12345,
            'email_address' => 'test@example.com'
        ];

        $cleanedRow = $cleaner->cleanRow($row);

        $this->assertEquals($row['id'], $cleanedRow['id']);
        $this->assertNotEquals($row['email_address'], $cleanedRow['email_address']);
    }

    public function testACustomCleanerCanBeRegisteredAndUsed(): void
    {
        $cleaner = new RowCleaner((object)[
            'cleanFields' => [
                'email_address' => 'custom_cleaner'
            ]
        ]);

        $row = [
            'id' => 12345,
            'email_address' => 'test@example.com'
        ];

        $customCleaner = $this->createMock(FieldCleaner::class);
        $customCleaner->method('clean')->willReturn('test!');

        $cleaner->registerCleaner($customCleaner, 'custom_cleaner');

        $cleanedRow = $cleaner->cleanRow($row);
        $this->assertSame('test!', $cleanedRow['email_address']);
    }

    public function testACustomCleanerOverridesDefaultAliases(): void
    {
        $cleaner = new RowCleaner((object)[
            'cleanFields' => [
                'email_address' => 'fakeemail'
            ]
        ]);

        $row = [
            'id' => 12345,
            'email_address' => 'test@example.com'
        ];

        $customCleaner = $this->createMock(FieldCleaner::class);
        $customCleaner->method('clean')->willReturn('test!');

        $cleaner->registerCleaner($customCleaner, 'fakeemail');

        $cleanedRow = $cleaner->cleanRow($row);
        $this->assertSame('test!', $cleanedRow['email_address']);
    }
}