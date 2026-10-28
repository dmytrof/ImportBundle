<?php

/*
 * This file is part of the DmytrofImportBundle package.
 *
 * (c) Dmytro Feshchenko <dmytro.feshchenko@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dmytrof\ImportBundle\Form\DataTransformer;

use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Form\DataTransformerInterface;

class ValueToIntegerTransformer implements DataTransformerInterface
{
    protected array $options;

    public function __construct(array $options)
    {
        $this->options = $options;
    }

    /**
     * Returns option
     */
    protected function getOption(string $name): mixed
    {
        return isset($this->options[$name]) ? $this->options[$name] : null;
    }

    /**
     * Transforms value
     */
    protected function transformValue($value): mixed
    {
        return (int) (string) $value;
    }

    /**
     * {@inheritdoc}
     */
    public function transform(mixed $data): mixed
    {
        if ($data instanceof \Traversable || is_array($data)) {
            $values = [];
            foreach ($data as $value) {
                array_push($values, $this->transformValue($value));
            }
            return $values;
        } else if (is_null($data)) {
            return null;
        }
        return $this->transformValue($data);
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform(mixed $data): mixed
    {
        if ($this->getOption('multiple')) {
            $collection = new ArrayCollection();
            foreach ((array) $data as $value) {
                $collection->add($this->transformValue($value));
            }
            if ($this->getOption('as_array')) {
                return $collection->toArray();
            }
            return $collection;
        }
        if (is_null($data)) {
            return null;
        }
        return $this->transformValue($data);
    }
}
