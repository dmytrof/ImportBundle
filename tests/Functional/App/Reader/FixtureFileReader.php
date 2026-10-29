<?php

namespace Dmytrof\ImportBundle\Tests\Functional\App\Reader;

use Dmytrof\ImportBundle\Model\ImportedData;
use Dmytrof\ImportBundle\Reader\AbstractReader;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Reads JSON from a local file instead of HTTP. A missing file means "no data" (end of pagination).
 */
class FixtureFileReader extends AbstractReader
{
    public const CODE = 'fixture_json';
    public const TITLE = 'Fixture JSON';

    public function getDataFromLink(string $link, array $options = [], ?SymfonyStyle $io = null): ImportedData
    {
        if (!is_file($link)) {
            return new ImportedData([]);
        }

        return new ImportedData($this->convertJsonToArray(file_get_contents($link)));
    }
}
