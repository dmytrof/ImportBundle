<?php

namespace Dmytrof\ImportBundle\Tests\Unit\Reader;

use Dmytrof\ImportBundle\Exception\ReaderException;
use Dmytrof\ImportBundle\Reader\XmlReader;
use Dmytrof\ImportBundle\Tests\Fixtures\IdentityTranslator;
use Dmytrof\ImportBundle\Tests\Fixtures\Reader\MockedXmlReader;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class XmlReaderTest extends TestCase
{
    public function testReadsXml(): void
    {
        $xml = '<?xml version="1.0"?><root><items><item><sku>A1</sku></item><item><sku>B2</sku></item></items></root>';
        $reader = (new MockedXmlReader(new IdentityTranslator()))->queueResponses([new Response(200, [], $xml)]);

        $this->assertSame('xml', XmlReader::getCode());
        $this->assertSame(
            ['root' => ['items' => ['item' => [['sku' => 'A1'], ['sku' => 'B2']]]]],
            $reader->getDataFromLink('https://example.com/feed.xml')->getData()
        );
    }

    public function testInvalidXml(): void
    {
        $reader = (new MockedXmlReader(new IdentityTranslator()))->queueResponses([new Response(200, [], 'not xml')]);

        $this->expectException(ReaderException::class);
        $reader->getDataFromLink('https://example.com/feed.xml');
    }
}
