<?php

/*
 * This file is part of the DmytrofImportBundle package.
 *
 * (c) Dmytro Feshchenko <dmytro.feshchenko@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dmytrof\ImportBundle\Model;

use Symfony\Component\HttpFoundation\Request;

class ImportFormData implements \ArrayAccess
{
    /**
     * @var array
     */
    protected $data;

    /**
     * @var array
     */
    protected $options = [];

    /**
     * ImportFormData constructor.
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Returns data
     * @param bool $removeEmptyProperties
     * @return array
     */
    public function getData(bool $removeEmptyProperties = false): array
    {
        $data = $this->data;
        if ($removeEmptyProperties) {
            $data = $this->removeEmptyProperties($data);
        }
        return $data;
    }

    /**
     * Removes empty properties
     * @param array $data
     * @return array
     */
    protected function removeEmptyProperties(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $value = $this->removeEmptyProperties($value);
            }
            if (is_null($value) || $value === '' || $value === []) {
                unset($data[$key]);
            }
        }
        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset)
    {
        return isset($this->data[$offset]);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        return $this->offsetExists($offset) ? $this->data[$offset]: null;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value)
    {
        $this->data[$offset] = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        unset($this->data[$offset]);
    }

    /**
     * Returns options
     * @param string $name
     * @return mixed|null
     */
    public function getOption(string $name)
    {
        return $this->hasOption($name) ? $this->options[$name] : null;
    }

    /**
     * Checks if option exists
     * @param string $name
     * @return bool
     */
    public function hasOption(string $name): bool
    {
        return array_key_exists($name, $this->options);
    }

    /**
     * Sets options
     * @param string $name
     * @param $value
     * @return ImportFormData
     */
    public function setOption(string $name, $value): self
    {
        $this->options[$name] = $value;
        return $this;
    }
}