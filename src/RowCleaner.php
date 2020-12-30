<?php
namespace Quidco\DbSampler;

/**
 * Clean single DB rows by callback maps
 */
class RowCleaner
{
    /**
     * Associative map of field => callback
     *
     * @var callable[]
     */
    protected $fieldCallbackMap;

    /**
     * RowCleaner constructor.
     *
     * @param \callable[] $fieldCallbackMap Associative map of field => callback
     */
    private function __construct($fieldCallbackMap)
    {
        $this->fieldCallbackMap = $fieldCallbackMap;
    }

    /**
     * Create a RowCleaner from config
     *
     * @param string[] $cleanSpec Assoc array of field => cleanerName (see FieldCleanerProvider::getCleanerByName)
     *
     * @return RowCleaner
     * @throws \RuntimeException If specification is invalid
     */
    public static function createFromSpecification($cleanSpec)
    {
        $fieldCleanerProvider = new FieldCleanerProvider();

        $fieldCleaners = [];
        foreach ($cleanSpec as $column => $cleaner) {
            $fieldCleaners[$column] = $fieldCleanerProvider->getCleanerByDescription($cleaner);
        }

        return new self($fieldCleaners);
    }

    /**
     * Clean a single row by reference
     *
     * @param mixed[] $row DB row to clean
     *
     * @return void
     */
    public function cleanRow(&$row)
    {
        foreach ($row as $field => $value) {
            if (array_key_exists($field, $this->fieldCallbackMap)) {
                $revised = $this->fieldCallbackMap[$field]($value);
                $row[$field] = $revised;
            }
        }
    }
}
