<?php

/*
 * This file is part of the DmytrofImportBundle package.
 *
 * (c) Dmytro Feshchenko <dmytro.feshchenko@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dmytrof\ImportBundle\Reader;

use Dmytrof\ImportBundle\Model\{ImportedData, Task};
use Dmytrof\ImportBundle\Reader\Options\ReaderOptionsInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

interface ReaderInterface
{
    /**
     * Returns code of reader
     * @return string
     */
    public static function getCode(): string;

    /**
     * Returns class for options
     * @return null|string
     */
    public static function getOptionsClass(): ?string;

    /**
     * Returns form class for options
     * @return null|string
     */
    public static function getOptionsFormClass(): ?string;

    /**
     * Checks if data is in root
     * @return bool
     */
    public static function isDataInRoot(): bool;

    /**
     * Returns title of reader
     * @return string
     */
    public function getTitle(): string;

    /**
     * Checks if reader has options
     * @return bool
     */
    public static function hasOptions(): bool;

    /**
     * Returns options
     * @return ReaderOptionsInterface|null
     */
    public function getOptions(): ?ReaderOptionsInterface;

    /**
     * Returns task
     * @return Task|null
     */
    public function getTask(): ?Task;

    /**
     * Sets task
     * @param Task $task
     * @return ReaderInterface
     */
    public function setTask(Task $task): self;

    /**
     * Returns data from link
     * @param string $link
     * @param array $options
     * @param SymfonyStyle|null $io
     * @return ImportedData
     */
    public function getDataFromLink(string $link, array $options = [], ?SymfonyStyle $io = null): ImportedData;
}
