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

use Dmytrof\ImportBundle\Model\ImportedData;

class JsonReader extends AbstractReader
{
    public const CODE = 'json';
    public const TITLE = 'dmytrof.reader.json.label';
    public const OPTIONS_CLASS = null;
    public const OPTIONS_FORM_CLASS = null;

    /**
     * {@inheritdoc}
     */
    public function getDataFromLink(string $link, array $options = []): ImportedData
    {
        $response = $this->getLinkResponse($link);

        return new ImportedData($this->convertJsonToArray($response->getBody()->getContents()));
    }
}