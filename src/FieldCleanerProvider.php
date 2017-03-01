<?php
namespace Quidco\DbSampler;

use Faker\Factory;
use Faker\Generator;

class FieldCleanerProvider
{
    /**
     * Faker instance to provide fake fields
     *
     * @var Generator
     */
    protected $faker;

    /**
     * Return a (guaranteed) Faker instance
     *
     * @return Generator
     */
    protected function getFaker()
    {
        if (!$this->faker) {
            $this->faker = Factory::create('en_GB');
        }

        return $this->faker;
    }

    /**
     * Return a closure by name that can clean a required field
     *
     * Closure should take a single parameter. When called, this will be the current field content.
     *
     * @param string $description Name of cleaner to use, with optional params after :
     *
     * @return \Closure|null
     * @throws \RuntimeException If invalid cleaner specified
     * @todo Accept name:param syntax to control field length, etc
     */
    public function getCleanerByDescription($description)
    {
        $parameters = explode(':', $description);
        $name = array_shift($parameters);

        $cleaner = null;
        $faker = $this->getFaker();
        switch (strtolower($name)) {
            case 'fakefullname':
                /** @noinspection PhpUnusedParameterInspection */
                $cleaner = function ($existing) use ($faker) {
                    return $faker->name;
                };
                break;
            case 'fakeemail':
                /** @noinspection PhpUnusedParameterInspection */
                $cleaner = function ($existing) use ($faker) {
                    return $faker->safeEmail;
                };
                break;
            case 'zero':
                /** @noinspection PhpUnusedParameterInspection */
                $cleaner = function ($existing) {
                    return 0;
                };
                break;
            case 'ipsum':
                /** @noinspection PhpUnusedParameterInspection */
                $cleaner = function ($existing) use ($faker) {
                    return $faker->sentence;
                };
                break;
            case 'emptystring':
                /** @noinspection PhpUnusedParameterInspection */
                $cleaner = function ($existing) {
                    return '';
                };
                break;
            case 'datetime':
                // Accepts a strtotime argument as a parameter, returns 2014-02-25 00:00:00 format
                /** @noinspection PhpUnusedParameterInspection */
                $cleaner = function ($existing) use ($parameters) {
                    $when = isset($parameters[0]) ? $parameters[0] : 'now';
                    $epoch = strtotime($when);
                    if ($epoch === false) {
                        throw new \RuntimeException("Invalid datetime parameter '$when' specified");
                    }

                    return date('Y-m-d H:i:s', $epoch);
                };
                break;
        }

        if (!$cleaner) {
            throw new \RuntimeException("Unknown cleaner type '$name' requested");
        }

        return $cleaner;
    }
}
