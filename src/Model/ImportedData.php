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

class ImportedData implements \Countable
{
    /**
     * Heading row
     * @var array
     */
    protected $headingRow;

    /**
     * Data
     * @var array
     */
    protected $data;

    /**
     * Data is in root
     * @var bool
     */
    protected $dataInRoot = false;

    /**
     * ImportedData constructor.
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Returns heading row
     * @return array|null
     */
    public function getHeadingRow(): ?array
    {
        return $this->headingRow;
    }

    /**
     * Checks if data has heading row
     * @return bool
     */
    public function hasHeadingRow(): bool
    {
        return !empty($this->getHeadingRow());
    }

    /**
     * Sets heading row
     * @param array|null $headingRow
     * @return ImportedData
     */
    public function setHeadingRow(?array $headingRow): self
    {
        $this->headingRow = $headingRow;
        return $this;
    }

    /**
     * Returns data
     * @return iterable
     */
    public function getData(): iterable
    {
        return $this->data;
    }

    /**
     * Returns example data
     * @return iterable
     */
    public function getExampleData(): iterable
    {
        return $this->getData();
    }

    /**
     * Prepares row
     * @param $row
     * @return array
     */
    public function prepareRow($row): array
    {
        return (array) $row;
    }

    /**
     * Checks if data is in root
     * @return bool
     */
    public function isDataInRoot(): bool
    {
        return $this->dataInRoot;
    }

    /**
     * Sets flag data in root
     * @param bool|null $dataInRoot
     * @return ImportedData
     */
    public function setDataInRoot(?bool $dataInRoot): self
    {
        $this->dataInRoot = (bool) $dataInRoot;
        return $this;
    }

    /**
     * Returns example data params
     * @return array
     */
    public function getExampleDataParams(): array
    {
        return [
            'data' => $this->getExampleData(),
            'headingRow' => $this->getHeadingRow(),
            'dataInRoot' => $this->isDataInRoot(),
        ];
    }

    /**
     * @inheritDoc
     */
    public function count()
    {
        return ($this->isDataInRoot()) ? count($this->data) : null;
    }
}