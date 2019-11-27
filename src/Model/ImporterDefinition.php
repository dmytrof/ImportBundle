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

use Dmytrof\ImportBundle\Importer\Options\ImporterOptionsInterface;

class ImporterDefinition
{
    /**
     * @var Task
     */
    private $task;

    /**
     * ImporterDefinition constructor.
     * @param Task $task
     */
    public function __construct(Task $task)
    {
        $this->task = $task;
    }

    /**
     * @return Task
     */
    public function getTask(): Task
    {
        return $this->task;
    }

    /**
     * Code
     * @return null|string
     */
    public function getCode(): ?string
    {
        return $this->getTask()->getImporterCode();
    }

    /**
     * Sets code
     * @param null|string $code
     * @return ImporterDefinition
     */
    public function setCode(?string $code): self
    {
        $this->getTask()->setImporterCode($code);
        return $this;
    }

    /**
     * Returns importer options
     * @return ImporterOptionsInterface|null
     */
    public function getOptions(): ?ImporterOptionsInterface
    {
        return $this->getTask()->getImporterOptions();
    }

    /**
     * Sets importer options
     * @param ImporterOptionsInterface|null $options
     * @return ImporterDefinition
     */
    public function setOptions(?ImporterOptionsInterface $options): self
    {
        $this->getTask()->updateImporterOptions($options);
        return $this;
    }

    /**
     * Magic method for virtual properties of importer options
     * @param $property
     * @return ImporterOptionsInterface|null
     */
    public function __get($property)
    {
        if ($property === $this->getCode()) {
            return $this->getOptions();
        } else {
            foreach ($this->getTask()->getImportersContainer() as $importer) {
                if ($property === $importer->getCode()) {
                    $optionsClass = $importer->getOptionsClass();
                    return new $optionsClass();
                }
            }
        }
        return null;
    }

    /**
     * Magic method for virtual properties of importer options
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