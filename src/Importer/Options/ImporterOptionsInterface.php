<?php

/*
 * This file is part of the DmytrofImportBundle package.
 *
 * (c) Dmytro Feshchenko <dmytro.feshchenko@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dmytrof\ImportBundle\Importer\Options;

use Dmytrof\ModelsManagementBundle\Model\ArrayConvertibleModelInterface;
use Dmytrof\ImportBundle\Model\{ImportableFields, ImportableFieldsOptions};

interface ImporterOptionsInterface extends \SplSubject, ArrayConvertibleModelInterface
{
    /**
     * Returns options hash scheme
     * @return array
     */
    public static function getOptionsHashScheme(): array;

    /**
     * Returns path delimiter
     * @return string
     */
    public static function getPathDelimiter(): string;

    /**
     * Returns id fields delimiter
     * @return string
     */
    public static function getIdFieldsDelimiter(): string;

    /**
     * Returns data path
     * @return null|string
     */
    public function getDataPath(): ?string;

    /**
     * Returns item hash id fields
     * @return array|null
     */
    public function getItemHashIdFields(): ?array;

    /**
     * Returns importable fields options
     * @return ImportableFieldsOptions|null
     */
    public function getImportableFieldsOptions(): ImportableFieldsOptions;

    /**
     * Prepares importable fields options
     * @param ImportableFields $importableFields
     * @return ImporterOptionsInterface
     */
    public function prepareImportableFieldsOptions(ImportableFields $importableFields): self;

    /**
     * Checks if force
     * @return bool
     */
    public function isForce(): bool;

    /**
     * Checks if sync data needed
     * @return bool
     */
    public function isSyncData(): bool;

    /**
     * Checks if skipping of existed rows needed
     * @return bool
     */
    public function isSkipExisted(): bool;
}