<?php


namespace Quidco\DbSampler;

class ReferenceStore
{
    protected $references = [];

    /**
     * Store an array of references by name for later use
     *
     * @param string $name       Variable name to store against
     * @param array  $references Array of references to store
     *
     * @return void
     */
    public function setReferencesByName($name, $references)
    {
        $this->references[$name] = $references;
    }

    /**
     * Return array of references by name
     *
     * @param string $name Variable name to look up
     *
     * @return array
     */
    public function getReferencesByName($name, $default = [])
    {
        return array_key_exists($name, $this->references) ? $this->references[$name] : $default;
    }
}
