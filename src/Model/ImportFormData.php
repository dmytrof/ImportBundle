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
     * @var string
     */
    protected $method = Request::METHOD_POST;

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
     * @return array
     */
    public function getData(): array
    {
        $data = $this->data;
        if ($this->isMethodPatch()) {
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
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Sets method
     * @param string $method
     * @return ImportFormData
     */
    public function setMethod(string $method): self
    {
        $this->method = $method;
        return $this;
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

    /**
     * Sets form method to Patch
     * @return ImportFormData
     */
    public function setMethodPatch(): self
    {
        $this->method = Request::METHOD_PATCH;
        return $this;
    }

    /**
     * Checks if method is patch
     * @return bool
     */
    public function isMethodPatch(): bool
    {
        return $this->method == Request::METHOD_PATCH;
    }
}