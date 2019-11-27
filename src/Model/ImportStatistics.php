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

class ImportStatistics
{
    /**
     * @var int
     */
    protected $all = 0;

    /**
     * @var int
     */
    protected $skipped = 0;

    /**
     * @var int
     */
    protected $scheduled = 0;

    /**
     * @var int
     */
    protected $duplicates = 0;

    /**
     * @var int
     */
    protected $created = 0;

    /**
     * @var int
     */
    protected $updated = 0;

    /**
     * @var int
     */
    protected $deleted = 0;

    /**
     * @var int
     */
    protected $errors = 0;

    /**
     * Resets all counters
     * @return ImportStatistics
     */
    public function reset(): self
    {
        $this->all = 0;
        $this->skipped = 0;
        $this->scheduled = 0;
        $this->duplicates = 0;
        $this->created = 0;
        $this->updated = 0;
        $this->deleted = 0;
        $this->errors = 0;

        return $this;
    }

    /**
     * Sets all
     * @param int $all
     * @return ImportStatistics
     */
    public function setAll(int $all = 0): self
    {
        $this->all = $all;
        return $this;
    }

    /**
     * Returns count of all
     * @return int
     */
    public function getAll(): int
    {
        return $this->all;
    }

    /**
     * Returns count of skipped
     * @return int
     */
    public function getSkipped(): int
    {
        return $this->skipped;
    }

    /**
     * Returns count of scheduled
     * @return int
     */
    public function getScheduled(): int
    {
        return $this->scheduled;
    }

    /**
     * Returns count of duplicates
     * @return int
     */
    public function getDuplicates(): int
    {
        return $this->duplicates;
    }

    /**
     * Returns count of created
     * @return int
     */
    public function getCreated(): int
    {
        return $this->created;
    }

    /**
     * Returns count of updated
     * @return int
     */
    public function getUpdated(): int
    {
        return $this->updated;
    }

    /**
     * Returns count of deleted
     * @return int
     */
    public function getDeleted(): int
    {
        return $this->deleted;
    }

    /**
     * Returns errors
     * @return int
     */
    public function getErrors(): int
    {
        return $this->errors;
    }

    /**
     * Increments count of all
     * @param int $count
     * @return ImportStatistics
     */
    public function incrementAll(int $count = 1): self
    {
        $this->all += $count;
        return $this;
    }

    /**
     * Increments count of skipped
     * @param int $count
     * @param bool $incrementAll
     * @return ImportStatistics
     */
    public function incrementSkipped(int $count = 1, bool $incrementAll = false): self
    {
        $this->skipped += $count;
        if ($incrementAll) {
            $this->incrementAll($count);
        }
        return $this;
    }

    /**
     * Increments count of scheduled
     * @param int $count
     * @param bool $incrementAll
     * @return ImportStatistics
     */
    public function incrementScheduled(int $count = 1, bool $incrementAll = false): self
    {
        $this->scheduled += $count;
        if ($incrementAll) {
            $this->incrementAll($count);
        }
        return $this;
    }

    /**
     * Increments count of duplicates
     * @param int $count
     * @param bool $incrementAll
     * @return ImportStatistics
     */
    public function incrementDuplicates(int $count = 1, bool $incrementAll = false): self
    {
        $this->duplicates += $count;
        if ($incrementAll) {
            $this->incrementAll($count);
        }
        return $this;
    }

    /**
     * Increments count of created
     * @param int $count
     * @param bool $incrementAll
     * @return ImportStatistics
     */
    public function incrementCreated(int $count = 1, bool $incrementAll = false): self
    {
        $this->created += $count;
        if ($incrementAll) {
            $this->incrementAll($count);
        }
        return $this;
    }

    /**
     * Increments count of updated
     * @param int $count
     * @param bool $incrementAll
     * @return ImportStatistics
     */
    public function incrementUpdated(int $count = 1, bool $incrementAll = false): self
    {
        $this->updated += $count;
        if ($incrementAll) {
            $this->incrementAll($count);
        }
        return $this;
    }

    /**
     * Increments count of deleted
     * @param int $count
     * @param bool $incrementAll
     * @return ImportStatistics
     */
    public function incrementDeleted(int $count = 1, bool $incrementAll = false): self
    {
        $this->skipped += $count;
        if ($incrementAll) {
            $this->incrementAll($count);
        }
        return $this;
    }

    /**
     * Increments count of errors
     * @param int $count
     * @param bool $incrementAll
     * @return ImportStatistics
     */
    public function incrementErrors(int $count = 1, bool $incrementAll = false): self
    {
        $this->errors += $count;
        if ($incrementAll) {
            $this->incrementAll($count);
        }
        return $this;
    }
}