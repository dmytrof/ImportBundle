<?php

/*
 * This file is part of the DmytrofImportBundle package.
 *
 * (c) Dmytro Feshchenko <dmytro.feshchenko@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dmytrof\ImportBundle\Reader\Options;

use Symfony\Component\Validator\Constraints as Assert;

class CsvReaderOptions extends AbstractReaderOptions
{
    /**
     * Has heading row
     * @var bool
     *
     * @Assert\Type("bool")
     */
    protected $hasHeadingRow = false;

    /**
     * Skip empty rows
     * @var bool
     *
     * @Assert\Type("bool")
     */
    protected $skipEmptyRows = true;

    /**
     * Delimiter
     * @var string
     *
     * @Assert\NotNull
     */
    protected $delimiter = ',';

    /**
     * Enclosure
     * @var string
     *
     * @Assert\NotNull
     */
    protected $enclosure = '"';

    /**
     * Escape
     * @var string
     *
     * @Assert\NotNull
     */
    protected $escape = '\\';

    /**
     * Returns hasHeadingRow
     * @return bool
     */
    public function getHasHeadingRow(): bool
    {
        return (bool) $this->hasHeadingRow;
    }

    /**
     * Sets hasHeadingRow
     * @param bool|null $hasHeadingRow
     * @return CsvReaderOptions
     */
    public function setHasHeadingRow(?bool $hasHeadingRow): self
    {
        $this->hasHeadingRow = (bool) $hasHeadingRow;
        $this->notify();
        return $this;
    }

    /**
     * Checks if empty rows must be skipped
     * @return bool
     */
    public function isSkipEmptyRows(): bool
    {
        return (bool) $this->skipEmptyRows;
    }

    /**
     * Sets skipEmptyRows
     * @param bool|null $skipEmptyRows
     * @return CsvReaderOptions
     */
    public function setSkipEmptyRows(?bool $skipEmptyRows): self
    {
        $this->skipEmptyRows = $skipEmptyRows;
        $this->notify();
        return $this;
    }

    /**
     * Returns delimiter
     * @return null|string
     */
    public function getDelimiter(): ?string
    {
        return $this->delimiter;
    }

    /**
     * Sets delimiter
     * @param null|string $delimiter
     * @return CsvReaderOptions
     */
    public function setDelimiter(?string $delimiter): self
    {
        $this->delimiter = $delimiter;
        $this->notify();
        return $this;
    }

    /**
     * Returns enclosure
     * @return null|string
     */
    public function getEnclosure(): ?string
    {
        return $this->enclosure;
    }

    /**
     * Sets enclosure
     * @param null|string $enclosure
     * @return CsvReaderOptions
     */
    public function setEnclosure(?string $enclosure): self
    {
        $this->enclosure = $enclosure;
        $this->notify();
        return $this;
    }

    /**
     * Returns escape
     * @return null|string
     */
    public function getEscape(): ?string
    {
        return $this->escape;
    }

    /**
     * Sets escape
     * @param null|string $escape
     * @return CsvReaderOptions
     */
    public function setEscape(?string $escape): self
    {
        $this->escape = $escape;
        $this->notify();
        return $this;
    }
}