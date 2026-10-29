<?php

namespace Dmytrof\ImportBundle\Tests\Fixtures\Reader;

use Dmytrof\ImportBundle\Reader\XmlReader;
use Dmytrof\ImportBundle\Tests\Fixtures\MockHttpClientTrait;

class MockedXmlReader extends XmlReader
{
    use MockHttpClientTrait;
}
