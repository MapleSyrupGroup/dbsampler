<?php


namespace Quidco\DbSampler\Writer;

use Doctrine\DBAL\Connection;

class Writer
{
    /**
     * Commands to run on the destination table after importing
     *
     * @var array
     */
    protected $postImportSql = [];

    /**
     * @var Connection
     */
    private $destination;

    public function __construct(\stdClass $config, Connection $destination)
    {
        $this->destination = $destination;

        $this->postImportSql = isset($config->postImportSql) ? $config->postImportSql : [];
    }

    public function write(string $tableName, array $rows): void
    {
        foreach ($rows as $row) {
            $this->sanitiseRowKeys($row);
            $this->destination->insert($tableName, $row);
        }

        foreach ($this->postImportSql as $sql) {
            $this->destination->exec($sql);
        }
    }

    /**
     * Issue: DBAL insert() does not check for reserved words being used as column names.
     *
     * So we have to clean the keys ourselves.
     *
     * *Very* special case initially as the general case is likely to be slow
     *
     * @param mixed[] $row Row to clean
     *
     * @return void
     *
     * @throws \RuntimeException If dest connection not configured
     */
    private function sanitiseRowKeys(array &$row): void
    {
        /** @noinspection ForeachOnArrayComponentsInspection */
        foreach (array_keys($row) as $key) {
            $row[$this->destination->quoteIdentifier($key)] = $row[$key];
            unset($row[$key]);
        }
    }
}
