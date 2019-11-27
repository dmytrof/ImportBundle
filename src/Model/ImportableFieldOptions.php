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

class ImportableFieldOptions implements \SplSubject
{
    /**
     * @var \SplObjectStorage
     */
    protected $observers;

    /**
     * Key in entry
     * @var string
     */
    protected $key;

    /**
     * Fallback key in entry
     * @var string
     */
    protected $fallbackKey;

    /**
     * Default value
     * @var string
     */
    protected $defaultValue;

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
    public function attach(\SplObserver $observer)
    {
        $this->getObservers()->attach($observer);
    }

    /**
     * {@inheritdoc}
     */
    public function detach(\SplObserver $observer)
    {
        $this->getObservers()->detach($observer);
    }

    /**
     * {@inheritdoc}
     */
    public function notify()
    {
        foreach ($this->getObservers() as $observer) {
            $observer->update($this);
        }
    }

    /**
     * Returns key
     * @return null|string
     */
    public function getKey(): ?string
    {
        return $this->key;
    }

    /**
     * Sets key
     * @param null|string $key
     * @return ImportableFieldOptions
     */
    public function setKey(?string $key): self
    {
        $this->key = $key;
        $this->notify();
        return $this;
    }

    /**
     * Returns fallback key
     * @return null|string
     */
    public function getFallbackKey(): ?string
    {
        return $this->fallbackKey;
    }

    /**
     * Sets fallback key
     * @param null|string $fallbackKey
     * @return ImportableFieldOptions
     */
    public function setFallbackKey(?string $fallbackKey): self
    {
        $this->fallbackKey = $fallbackKey;
        $this->notify();
        return $this;
    }

    /**
     * Returns default value
     * @return null|string
     */
    public function getDefaultValue(): ?string
    {
        return $this->defaultValue;
    }

    /**
     * Sets default value
     * @param null|string $defaultValue
     * @return ImportableFieldOptions
     */
    public function setDefaultValue(?string $defaultValue): self
    {
        $this->defaultValue = $defaultValue;
        $this->notify();
        return $this;
    }
}