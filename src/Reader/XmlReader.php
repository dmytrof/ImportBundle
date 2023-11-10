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

use Dmytrof\ImportBundle\{Exception\ReaderException, Model\ImportedData};
use Symfony\Component\Console\Style\SymfonyStyle;
use Laminas\Xml2Json\{Xml2Json, Exception\RuntimeException};

class XmlReader extends AbstractReader
{
    public const CODE = 'xml';
    public const TITLE = 'dmytrof.reader.xml.label';
    public const OPTIONS_CLASS = null;
    public const OPTIONS_FORM_CLASS = null;

    /**
     * {@inheritdoc}
     */
    public function getDataFromLink(string $link, array $options = [], ?SymfonyStyle $io = null): ImportedData
    {
        $response = $this->getLinkResponse($link, $options, $io);

        try {
            $json = Xml2Json::fromXml($response->getBody()->getContents(), false);
        } catch (RuntimeException $e) {
            throw new ReaderException($e->getMessage());
        }

        return new ImportedData($this->convertJsonToArray($json));
    }
}
