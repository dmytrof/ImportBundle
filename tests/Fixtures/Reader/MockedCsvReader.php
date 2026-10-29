<?php

namespace Dmytrof\ImportBundle\Tests\Fixtures\Reader;

use Dmytrof\ImportBundle\Reader\CsvReader;
use Dmytrof\ImportBundle\Tests\Fixtures\MockHttpClientTrait;

class MockedCsvReader extends CsvReader
{
    use MockHttpClientTrait;
}
