<?php
namespace Quidco\DbSampler\Sampler;

use Quidco\DbSampler\BaseSampler;

/**
 * Sample DB rows that match specific values
 *
 * Can create IN constraints by setting an array as the RHS of the constraint, otherwise set a scalar
 * Can specify a list of WHERE clauses
 *
 * eg:
 * "api_clients": {
 *     "sampler": "matched",
 *     "constraints": {
 *         "cobrand_prefix": [
 *             "candis",
 *             "www"
 *         ]
 *     },
 *     "where": [
 *         "created > NOW()"
 *     ]
 * }
 */
class MatchedRows extends BaseSampler implements Sampler
{
    /**
     * Assoc array of field => static value
     *
     * @var array
     */
    protected $constraints;
    /**
     * @var array
     */
    private $where;

    /**
     * Return a unique name for this sampler for informational purposes
     *
     * @return string
     * @inheritdoc
     */
    public function getName()
    {
        return 'Matched';
    }

    /**
     * Return all rows that this sampler would copy
     *
     * @inheritdoc
     */
    public function getRows(): array
    {
        $this->where = $this->config->where ?? [];
        if ($this->where) {
            $this->constraints = $this->config->constraints ?? [];
        } else {
            $this->constraints = (array)$this->demandParameterValue($this->config, 'constraints');
        }

        $queryBuilder = $this->sourceConnection->createQueryBuilder()->select('*')->from($this->tableName);
        $queryBuilder->where('1');

        foreach ($this->constraints as $field => $value) {
            // Handle remembered reference variables
            if (is_string($value) && strpos($value, '$') === 0) {
                $variable = substr($value, 1);
                $value = $this->referenceStore->getReferencesByName($variable, null);
                if (is_null($value)) {
                    throw new \RuntimeException("'\${$variable}' is not a recognised remembered value");
                }
            }

            if (is_array($value)) {
                if (count($value)) {
                    $questionMarks = implode(', ', array_pad([], count($value), '?'));
                    $queryBuilder->andWhere(
                        $this->sourceConnection->quoteIdentifier($field) . ' IN (' . $questionMarks . ')'
                    );

                    foreach ((array)$value as $alternate) { // (array) required to keep static analysis from screaming
                        $queryBuilder->createPositionalParameter($alternate);
                    }
                } else {
                    $queryBuilder->andWhere('0');
                }
            } else {
                $queryBuilder->andWhere($this->sourceConnection->quoteIdentifier($field) . ' = ?');
                $queryBuilder->createPositionalParameter($value);
            }
        }

        foreach ($this->where as $where) {
            $queryBuilder->andWhere($where);
        }

        if ($this->limit) {
            $queryBuilder->setMaxResults($this->limit);
        }

        $query = $queryBuilder->execute();

        return $query->fetchAll();
    }
}
