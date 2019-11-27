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

use Doctrine\Common\Collections\ArrayCollection;
use Dmytrof\ImportBundle\Exception\InvalidArgumentException;

class ImportableFields extends ArrayCollection
{
    /**
     * ImportableFields constructor.
     * @param array $elements
     */
    public function __construct(array $elements = [])
    {
        parent::__construct([]);
        foreach ($elements as $element) {
            $this->add($element);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function set($key, $value)
    {
        $this->add($value);
    }

    /**
     * {@inheritDoc}
     */
    public function add($element)
    {
        if (!$element instanceof ImportableField) {
            throw new InvalidArgumentException(sprintf('Element must be %s. Got: %s', ImportableField::class, gettype($element)));
        }
        parent::set($element->getName(), $element);

        return true;
    }

    /**
     * Sorts by positions
     * @return ImportableFields
     */
    public function sortPositions(): self
    {
        $fields = $this->toArray();
        uasort($fields, function ($a, $b) {
            if ($a->getPosition() == $b->getPosition()) {
                return 0;
            }
            return ($a->getPosition() < $b->getPosition()) ? -1 : 1;
        });

        $this->clear();
        foreach ($fields as $field) {
            $this->add($field);
        }
        return $this;
    }

    /**
     * Returns fields options
     * @return ImportableFieldOptions[]
     */
    public function getFieldsOptions(): array
    {
        $options = [];
        foreach ($this as $field) {
            $options[$field->getName()] = $field->getOptions();
        }
        return $options;
    }

    /**
     * Sets fields options
     * @param ImportableFieldsOptions $fieldsOptions
     * @return ImportableFields
     */
    public function setFieldsOptions(ImportableFieldsOptions $fieldsOptions): self
    {
        foreach ($fieldsOptions as $name => $options) {
            if ($this->containsKey($name)) {
                $this->get($name)->setOptions($options);
            }
        }
        return $this;
    }
}