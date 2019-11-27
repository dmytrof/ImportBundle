<?php

/*
 * This file is part of the DmytrofImportBundle package.
 *
 * (c) Dmytro Feshchenko <dmytro.feshchenko@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dmytrof\ImportBundle\Service;

use Doctrine\Common\Collections\{Collection, ArrayCollection};
use Dmytrof\ImportBundle\Exception\ReaderException;
use Dmytrof\ImportBundle\Reader\ReaderInterface;

class ReadersContainer implements \IteratorAggregate
{
    /**
     * @var Collection|ReaderInterface[]
     */
	protected $readers;

    /**
     * ReadersContainer constructor.
     * @param iterable $readers
     */
    public function __construct(iterable $readers)
    {
        $this->readers = new ArrayCollection();
        foreach ($readers as $reader) {
            $this->addReader($reader);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        return $this->readers->getIterator();
    }

    /**
     * Adds reader
     * @param ReaderInterface $reader
     * @return ReadersContainer
     */
    public function addReader(ReaderInterface $reader): self
    {
        $this->readers->set($reader->getCode(), $reader);
        return $this;
    }

    /**
     * Returns reader by code
     * @param null|string $code
     * @return ReaderInterface
     */
    public function get(?string $code): ReaderInterface
    {
        if (!$this->has($code)) {
            throw new ReaderException(sprintf('Undefined reader with code %s', $code));
        }
        return $this->readers->get($code);
    }

    /**
     * Checks if container has reader with code
     * @param null|string $code
     * @return bool
     */
    public function has(?string $code): bool
    {
        return $this->readers->containsKey($code);
    }

    /**
     * Returns array of readers titles
     * @return array
     */
    public function getReadersTitles(): array
    {
        $titles = [];
        foreach ($this->readers as $reader) {
            $titles[$reader->getCode()] = $reader->getTitle();
        }
        return $titles;
    }

    /**
     * Returns array of readers codes
     * @return array
     */
    public function getReadersCodes(): array
    {
        return $this->readers->getKeys();
    }
}