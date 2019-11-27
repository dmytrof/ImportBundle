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

use Dmytrof\ImportBundle\Model\{
    ImportableFieldOptions, ImportableFields, ImportableFieldsOptions
};
use Symfony\Component\Validator\Constraints as Assert;

class ImporterOptions implements ImporterOptionsInterface, \SplObserver
{
    public const PATH_DELIMITER = '/';
    public const ID_FIELDS_DELIMITER = ',';

    /**
     * @var \SplObjectStorage
     */
    protected $observers;

    /**
     * Path to data entries (items)
     * @var string
     */
    protected $dataPath;

    /**
     * Entry (item) hash id fields
     * @var array
     *
     * @Assert\Count(min=1)
     */
    protected $itemHashIdFields;

    /**
     * Importable fields options
     * @var ImportableFieldsOptions
     */
    protected $importableFieldsOptions;

    /**
     * Returns path delimiter
     * @return string
     */
    public static function getPathDelimiter(): string
    {
        return static::PATH_DELIMITER;
    }

    /**
     * Returns id fields delimiter
     * @return string
     */
    public static function getIdFieldsDelimiter(): string
    {
        return static::ID_FIELDS_DELIMITER;
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
     * Returns data path
     * @return null|string
     */
    public function getDataPath(): ?string
    {
        return $this->dataPath;
    }

    /**
     * Sets data path
     * @param null|string $dataPath
     * @return ImporterOptions
     */
    public function setDataPath(?string $dataPath): self
    {
        $this->dataPath = $dataPath;
        $this->notify();
        return $this;
    }

    /**
     * Returns item hash id fields
     * @return array|null
     */
    public function getItemHashIdFields(): ?array
    {
        return $this->itemHashIdFields;
    }

    /**
     * Sets item hash id fields
     * @param array|null $itemHashIdFields
     * @return ImporterOptions
     */
    public function setItemHashIdFields(?array $itemHashIdFields): self
    {
        $this->itemHashIdFields = $itemHashIdFields;
        $this->notify();
        return $this;
    }

    /**
     * Returns item hash id fields as string
     * @return string|null
     *
     * @Assert\NotBlank
     */
    public function getItemHashIdFieldsStr(): ?string
    {
        return join($this->getIdFieldsDelimiter(), (array) $this->getItemHashIdFields());
    }

    /**
     * Sets item hash id fields as string
     * @param null|string $itemHashIdFieldsStr
     * @return ImporterOptions
     */
    public function setItemHashIdFieldsStr(?string $itemHashIdFieldsStr): self
    {
        $this->setItemHashIdFields(explode($this->getIdFieldsDelimiter(), (string) $itemHashIdFieldsStr));
        return $this;
    }

    /**
     * Returns importable fields options
     * @return ImportableFieldsOptions|null
     */
    public function getImportableFieldsOptions(): ImportableFieldsOptions
    {
        if (is_null($this->importableFieldsOptions)) {
            $this->importableFieldsOptions = new ImportableFieldsOptions();
        }
        if (!$this->importableFieldsOptions->getObservers()->contains($this)) {
            $this->importableFieldsOptions->attach($this);
        }
        return $this->importableFieldsOptions;
    }

    /**
     * Sets importable fields options
     * @param ImportableFieldsOptions|null $importableFieldsOptions
     * @return ImporterOptions
     */
    public function setImportableFieldsOptions(?ImportableFieldsOptions $importableFieldsOptions): self
    {
        $this->importableFieldsOptions = $importableFieldsOptions;
        $this->notify();
        return $this;
    }

    /**
     * Prepares importable fields options
     * @param ImportableFields $importableFields
     * @return ImporterOptionsInterface
     */
    public function prepareImportableFieldsOptions(ImportableFields $importableFields): ImporterOptionsInterface
    {
        $fieldsOptions = $this->getImportableFieldsOptions();
        foreach ($importableFields as $field) {
            if (!$fieldsOptions->containsKey($field->getName())) {
                $fieldsOptions->set($field->getName(), new ImportableFieldOptions());
            }
        }
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function update(\SplSubject $subject): void
    {
        if ($subject instanceof ImportableFieldsOptions) {
            $this->notify();
        }
    }
}