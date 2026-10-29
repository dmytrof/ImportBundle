<?php

namespace Dmytrof\ImportBundle\Tests\Fixtures\Reader;

use Dmytrof\ImportBundle\Reader\JsonReader;
use Dmytrof\ImportBundle\Tests\Fixtures\MockHttpClientTrait;

class MockedJsonReader extends JsonReader
{
    use MockHttpClientTrait;
}
