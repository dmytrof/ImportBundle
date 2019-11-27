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

use Dmytrof\ImportBundle\{
    Exception\ReaderException, Model\ImportedData
};
use Zend\Xml2Json\{
    Exception\RuntimeException, Xml2Json
};

class XmlReader extends AbstractReader
{
    public const CODE = 'xml';
    public const TITLE = 'label.import_reader.xml';
    public const OPTIONS_CLASS = null;
    public const OPTIONS_FORM_CLASS = null;

    /**
     * {@inheritdoc}
     */
    public function getDataFromLink(string $link, array $options = []): ImportedData
    {
        $response = $this->getLinkResponse($link);

        try {
            $json = Xml2Json::fromXml($response->getBody()->getContents(), false);
        } catch (RuntimeException $e) {
            throw new ReaderException($e->getMessage());
        }

        return new ImportedData($this->convertJsonToArray($json));
    }
}