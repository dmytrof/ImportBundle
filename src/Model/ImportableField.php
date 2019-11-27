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

class ImportableField
{
    /**
     * Name of property
     * @var string
     */
    protected $name;

    /**
     * Label of property
     * @var string
     */
    protected $label;

    /**
     * Position of property
     * @var int
     */
    protected $position = 100;

    /**
     * Field options
     * @var ImportableFieldOptions
     */
    protected $options;

    /**
     * ImportableField constructor.
     * @param string $name
     * @param null|string $label
     * @param int|null $position
     */
    public function __construct(string $name, ?string $label = null, ?int $position = null)
    {
        $this->setName($name);
        $this->setLabel($label);
        $this->setPosition($position);
    }

    /**
     * Returns name
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Sets name
     * @param string $name
     * @return ImportableField
     */
    protected function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Returns label
     * @return null|string
     */
    public function getLabel(): ?string
    {
        return $this->label;
    }

    /**
     * Sets label
     * @param null|string $label
     * @return ImportableField
     */
    public function setLabel(?string $label): self
    {
        $this->label = $label;
        return $this;
    }

    /**
     * Returns position
     * @return int
     */
    public function getPosition(): int
    {
        return $this->position;
    }

    /**
     * Sets position
     * @param int|null $position
     * @return ImportableField
     */
    public function setPosition(?int $position): self
    {
        $this->position = is_null($position) ? 100 : $position;
        return $this;
    }

    /**
     * Returns options
     * @return ImportableFieldOptions
     */
    public function getOptions(): ImportableFieldOptions
    {
        if (is_null($this->options)) {
            $this->options = new ImportableFieldOptions();
        }
        return $this->options;
    }

    /**
     * Sets options
     * @param ImportableFieldOptions|null $options
     * @return ImportableField
     */
    public function setOptions(?ImportableFieldOptions $options): self
    {
        $this->options = $options;
        return $this;
    }
}