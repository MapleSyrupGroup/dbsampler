<?php


namespace Quidco\DbSampler\Cleaner;

use Faker\Factory;
use Quidco\DbSampler\Cleaner\FieldCleaner\DateTime;
use Quidco\DbSampler\Cleaner\FieldCleaner\EmailAddress;
use Quidco\DbSampler\Cleaner\FieldCleaner\EmptyString;
use Quidco\DbSampler\Cleaner\FieldCleaner\Faker;
use Quidco\DbSampler\Cleaner\FieldCleaner\FullName;
use Quidco\DbSampler\Cleaner\FieldCleaner\LoremIpsum;
use Quidco\DbSampler\Cleaner\FieldCleaner\NullCleaner;
use Quidco\DbSampler\Cleaner\FieldCleaner\RandomDigits;
use Quidco\DbSampler\Cleaner\FieldCleaner\User;
use Quidco\DbSampler\Cleaner\FieldCleaner\Zero;

class FieldCleanerFactory
{
    public const CLEANERS = [
        'fakefullname' => FullName::class,
        'fakeemail' => EmailAddress::class,
        'fakeuser' => User::class,
        'faker' => Faker::class,
        'zero' => Zero::class,
        'null' => NullCleaner::class,
        'ipsum' => LoremIpsum::class,
        'emptystring' => EmptyString::class,
        'datetime' => DateTime::class,
        'randomdigits' => RandomDigits::class,
    ];

    public static function getCleaner(CleanerConfig $cleanerConfig): FieldCleaner
    {
        if (false === \array_key_exists($cleanerConfig->getName(), self::CLEANERS)) {
            throw new \RuntimeException('Configured cleaner is not valid!');
        }

        $cleanerClass = self::CLEANERS[$cleanerConfig->getName()];

        return new $cleanerClass(Factory::create('en_GB'));
    }
}