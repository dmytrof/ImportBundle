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

use Symfony\Component\Filesystem\Filesystem;

class ImportedDataFile extends ImportedData implements \IteratorAggregate
{
    /**
     * @var string
     */
    protected $filename;

    /**
     * @var \SplFileObject
     */
    protected $fileObject;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * ImportedDataFile constructor.
     * @param string $filename
     */
    public function __construct(string $filename)
    {
        parent::__construct([]);
        $this->filename = $filename;
        $this->dataInRoot = true;
    }

    /**
     * Returns filesystem
     * @return Filesystem
     */
    public function getFilesystem(): Filesystem
    {
        if (is_null($this->filesystem)) {
            $this->filesystem = new Filesystem();
        }
        return $this->filesystem;
    }

    /**
     * Adds row to file
     * @param array $row
     * @param bool $firstRow
     * @return ImportedDataFile
     */
    public function addRow(array $row, $firstRow = true): self
    {
        $json = json_encode($row, JSON_UNESCAPED_UNICODE);
        if (!empty($json)) {
            $this->getFilesystem()->appendToFile($this->filename, (!$firstRow ? PHP_EOL : '').$json);
        }
        return $this;
    }

    /**
     * Returns file object
     * @return \SplFileObject
     */
    public function getFileObject(): \SplFileObject
    {
        if (is_null($this->fileObject)) {
            $this->fileObject = new \SplFileObject($this->filename, 'r');
        }
        return $this->fileObject;
    }

    /**
     * @inheritDoc
     */
    public function count()
    {
        $file = $this->getFileObject();
        $file->seek(PHP_INT_MAX);
        return $file->key() + 1;
    }

    /**
     * Prepares row
     * @param string|null $row
     * @return array
     */
    public function prepareRow($row): array
    {
        return (array) json_decode($row, true, 512, JSON_UNESCAPED_UNICODE);
    }

    /**
     * Returns data
     * @return iterable
     */
    public function getData(): iterable
    {
        return $this;
    }

    /**
     * Returns example data
     * @return iterable
     */
    public function getExampleData(): iterable
    {
        $data = [];
        $i=0;
        foreach ($this->getData() as $row) {
            array_push($data, $this->prepareRow($row));
            $i++;
            if ($i >= 20) {
                break;
            }
        }
        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        return $this->getFileObject();
    }
}