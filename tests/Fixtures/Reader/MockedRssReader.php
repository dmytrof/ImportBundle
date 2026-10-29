<?php

namespace Dmytrof\ImportBundle\Tests\Fixtures\Reader;

use Dmytrof\ImportBundle\Reader\RssReader;
use Dmytrof\ImportBundle\Tests\Fixtures\MockHttpClientTrait;

class MockedRssReader extends RssReader
{
    use MockHttpClientTrait;
}
