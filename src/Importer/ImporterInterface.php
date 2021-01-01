<?php

/*
 * This file is part of the DmytrofImportBundle package.
 *
 * (c) Dmytro Feshchenko <dmytro.feshchenko@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dmytrof\ImportBundle\Importer;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Console\{Output\OutputInterface, Style\SymfonyStyle};
use Dmytrof\ImportBundle\{Importer\Options\ImporterOptionsInterface, Manager\ItemManager};
use Dmytrof\ImportBundle\Model\{ImportableFields, ImportedData, ImportFormData, ImportStatistics, Item, Task};

interface ImporterInterface
{
    /**
     * Returns code of importer
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
     * Returns form class for importer
     * @return null|string
     */
    public static function getImporterFormClass(): ?string;

    /**
     * Returns form options for importer
     * @return array
     */
    public function getImporterFormOptions(): array;

    /**
     * Returns title of importer
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
     * @return ImporterOptionsInterface|null
     */
    public function getOptions(): ?ImporterOptionsInterface;

    /**
     * Returns options as array
     * @return array|null
     */
    public function getOptionsArr(): ?array;

    /**
     * Returns translator
     * @return null|TranslatorInterface
     */
    public function getTranslator(): ?TranslatorInterface;

    /**
     * Sets translator
     * @param TranslatorInterface $translator
     * @return ImporterInterface
     */
    public function setTranslator(TranslatorInterface $translator): self;

    /**
     * Returns item manager
     * @return ItemManager|null
     */
    public function getItemManager(): ?ItemManager;

    /**
     * Sets item manager
     * @param ItemManager $itemManager
     * @return ImporterInterface
     */
    public function setItemManager(ItemManager $itemManager): self;

    /**
     * Returns task
     * @return Task|null
     */
    public function getTask(): ?Task;

    /**
     * Sets task
     * @param Task $task
     * @return ImporterInterface
     */
    public function setTask(Task $task): self;

    /**
     * Returns output
     * @return SymfonyStyle
     */
    public function getOutput(): SymfonyStyle;

    /**
     * Sets output
     * @param null|OutputInterface $output
     * @return ImporterInterface
     */
    public function setOutput(?OutputInterface $output): self;

    /**
     * Returns logger
     * @return LoggerInterface
     */
    public function getLogger(): LoggerInterface;

    /**
     * Sets logger
     * @param LoggerInterface|null $logger
     * @return ImporterInterface
     */
    public function setLogger(?LoggerInterface $logger): ImporterInterface;

    /**
     * Imports data
     * @param ImportedData $data
     * @return ImporterInterface
     */
    public function importData(ImportedData $data): self;

    /**
     * Imports data from task
     * @param Task $task
     * @param array $options
     * @return $this
     */
    public function importTask(Task $task, array $options = []): self;

    /**
     * Imports data from item
     * @param Item $item
     * @return ImporterInterface
     */
    public function importItem(Item $item): self;

    /**
     * Returns importable fields
     * @return ImportableFields
     */
    public function getImportableFields(): ImportableFields;

    /**
     * Returns imports statistics
     * @return ImportStatistics
     */
    public function getImportStatistics(): ImportStatistics;

    /**
     * Returns import form data
     * @param Item $importedItem
     * @return ImportFormData
     */
    public function getImportFormData(Item $importedItem): ImportFormData;

    /**
     * Returns data from importFormData
     * @param ImportFormData $importFormData
     * @param Item $importedItem
     * @return array
     */
    public function getDataFromImportFormData(ImportFormData $importFormData, Item $importedItem): array;
}