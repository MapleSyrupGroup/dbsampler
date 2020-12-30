<?php

namespace Quidco\DbSampler\Cleaner\FieldCleaner;

use Faker\Generator;
use Quidco\DbSampler\Cleaner\FieldCleaner;

class EmailAddress implements FieldCleaner
{
    /**
     * @var Generator
     */
    private $faker;

    public function __construct(Generator $faker)
    {
        $this->faker = $faker;
    }

    public function clean(array $parameters, ?string $originalValue = null)
    {
        return $this->faker->safeEmail;
    }
}