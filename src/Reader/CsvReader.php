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

use Symfony\Component\Filesystem\{
    Exception\IOException, Filesystem
};
use Dmytrof\ImportBundle\{Exception\ReaderException,
    Form\Type\Reader\CsvReaderOptionsType,
    Model\ImportedData,
    Model\ImportedDataFile,
    Reader\Options\CsvReaderOptions,
    Reader\Options\ReaderOptionsInterface};
use Symfony\Component\OptionsResolver\OptionsResolver;

class CsvReader extends AbstractReader
{
    public const CODE = 'csv';
    public const TITLE = 'label.import_reader.csv';
    public const OPTIONS_CLASS = CsvReaderOptions::class;
    public const OPTIONS_FORM_CLASS = CsvReaderOptionsType::class;
    public const DATA_IN_ROOT = true;

    /**
     * Returns options
     * @return CsvReaderOptions
     */
    public function getOptions(): ?ReaderOptionsInterface
    {
        return parent::getOptions();
    }

    /**
     * Checks if row is empty
     * @param array $row
     * @return bool
     */
    protected function isEmptyRow(array $row): bool
    {
        foreach ($row as $value) {
            if (!empty($value)) {
                return false;
            }
        }
        return true;
    }



    /**
     * {@inheritdoc}
     */
    public function getDataFromLink(string $link, array $options = []): ImportedData
    {
        $options = $this->configureGetDataFromLinkOptions(new OptionsResolver())->resolve($options);
        $response = $this->getLinkResponse($link);

        if (!$this->hasResponseHeaderAnyValue($response, 'Content-type', ['text/csv', 'application/octet-stream'])) {
            throw new ReaderException(sprintf('Invalid CSV data'));
        }

        try {
            $filesystem = new Filesystem();
            $csvFileName = $filesystem->tempnam('/tmp','csv');
            $dataFileName = $filesystem->tempnam('/tmp','data');
            $filesystem->dumpFile($csvFileName, $response->getBody()->getContents());

            $file = new \SplFileObject($csvFileName);
            $file->setFlags(\SplFileObject::READ_CSV);
            $file->setCsvControl($this->getOptions()->getDelimiter(), $this->getOptions()->getEnclosure(), $this->getOptions()->getEscape());

            $importedData = new ImportedDataFile($dataFileName);

            $headingRow = null;
            $firstRow = true;
            $i=0;
            foreach ($file as $row) {
                if ($this->isEmptyRow($row)) {
                    continue;
                }
                if (is_null($headingRow) && $this->getOptions()->getHasHeadingRow()) {
                    $headingRow = $row;
                    continue;
                }
                $importedData->addRow($row, $firstRow);
                $firstRow = false;
                $i++;
                if ($options['exampleData'] && $i > 100) {
                    break;
                }
            }
            $importedData->setHeadingRow($headingRow);

            return $importedData;
        } catch (IOException $e) {
            throw new ReaderException(sprintf('Temporary file creating error: %s', $e->getMessage()));
        }
    }
}