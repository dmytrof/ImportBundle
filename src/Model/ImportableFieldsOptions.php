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
use Dmytrof\ImportBundle\Exception\{ImportableFieldsOptionsException, InvalidArgumentException};

class ImportableFieldsOptions extends ArrayCollection implements \SplObserver, \SplSubject
{
    /**
     * @var \SplObjectStorage
     */
    protected $observers;

    /**
     * ImportableFieldsOptions constructor.
     * @param array $elements
     */
    public function __construct(array $elements = [])
    {
        parent::__construct([]);
        $this->setFieldsOptionsArr($elements);
    }

    /**
     * {@inheritDoc}
     */
    public function set(string|int $key, mixed $value): void
    {
        if (!$value instanceof ImportableFieldOptions && !is_null($value)) {
            throw new InvalidArgumentException(sprintf('Element must be %s. Got: %s', ImportableFieldOptions::class, gettype($value)));
        }
        if (!is_null($value)) {
            $value->attach($this);
            parent::set($key, $value);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function add(mixed $element): void
    {
        throw new ImportableFieldsOptionsException('Unable to add new elements. Please, use set() method instead');
    }

    public function __get(string $name): mixed
    {
        return $this->get($name);
    }

    public function __isset(string $name): bool
    {
        return $this->containsKey($name);
    }

    public function __set(string $name, mixed $value): void
    {
        $this->set($name, $value);
    }

    /**
     * Returns fields options array
     * @return ImportableFieldOptions[]
     */
    public function getFieldsOptionsArr(): array
    {
        return $this->toArray();
    }

    /**
     * Sets fields options array
     * @param ImportableFieldOptions[] $elements
     * @return ImportableFieldsOptions
     */
    public function setFieldsOptionsArr($elements): self
    {
        $this->clear();
        foreach ($elements as $key => $element) {
            $this->set($key, $element);
        }
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function update(\SplSubject $subject): void
    {
        if ($subject instanceof ImportableFieldOptions) {
            $this->notify();
        }
    }

    /**
     * @return \SplObjectStorage
     */
    public function getObservers(): \SplObjectStorage
    {
        if (is_null($this->observers)) {
            $this->observers = new \SplObjectStorage();
        }
        return $this->observers;
    }

    /**
     * {@inheritdoc}
     */
    public function attach(\SplObserver $observer): void
    {
        $this->getObservers()->attach($observer);
    }

    /**
     * {@inheritdoc}
     */
    public function detach(\SplObserver $observer): void
    {
        $this->getObservers()->detach($observer);
    }

    /**
     * {@inheritdoc}
     */
    public function notify(): void
    {
        foreach ($this->getObservers() as $observer) {
            $observer->update($this);
        }
    }
}
