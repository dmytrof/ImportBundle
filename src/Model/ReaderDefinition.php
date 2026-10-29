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

use Dmytrof\ImportBundle\Reader\Options\ReaderOptionsInterface;

class ReaderDefinition
{
    public function __construct(
        private Task $task,
    ) {
    }

    public function getTask(): Task
    {
        return $this->task;
    }

    public function getCode(): ?string
    {
        return $this->getTask()->getReaderCode();
    }

    /**
     * Sets code
     * @param null|string $code
     * @return ReaderDefinition
     */
    public function setCode(?string $code): self
    {
        $this->getTask()->setReaderCode($code);
        return $this;
    }

    /**
     * Returns reader options
     * @return ReaderOptionsInterface|null
     */
    public function getOptions(): ?ReaderOptionsInterface
    {
        return $this->getTask()->getReaderOptions();
    }

    /**
     * Sets reader options
     * @param ReaderOptionsInterface|null $options
     * @return ReaderDefinition
     */
    public function setOptions(?ReaderOptionsInterface $options): self
    {
        $this->getTask()->updateReaderOptions($options);
        return $this;
    }

    /**
     * Magic method for virtual properties of reader options
     * @param $property
     * @return ReaderOptionsInterface|null
     */
    public function __get($property)
    {
        if ($property === $this->getCode()) {
            return $this->getOptions();
        } else {
            foreach ($this->getTask()->getReadersContainer() as $reader) {
                if ($property === $reader->getCode() && $reader->hasOptions()) {
                    $optionsClass = $reader->getOptionsClass();
                    return new $optionsClass();
                }
            }
        }
        return null;
    }

    /**
     * Magic method for virtual properties of reader options (needed by Symfony PropertyAccess)
     * @param $property
     * @return bool
     */
    public function __isset($property): bool
    {
        if ($property === $this->getCode()) {
            return true;
        }
        foreach ($this->getTask()->getReadersContainer() ?? [] as $item) {
            if ($property === $item->getCode()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Magic method for virtual properties of reader options
     * @param $property
     * @param $value
     */
    public function __set($property, $value)
    {
        if ($property === $this->getCode()) {
            $this->setOptions($value);
        }
    }
}
