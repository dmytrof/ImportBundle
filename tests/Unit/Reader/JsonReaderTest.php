<?php

namespace Dmytrof\ImportBundle\Tests\Unit\Reader;

use Dmytrof\ImportBundle\Exception\ReaderException;
use Dmytrof\ImportBundle\Model\ImportedData;
use Dmytrof\ImportBundle\Reader\JsonReader;
use Dmytrof\ImportBundle\Tests\Fixtures\IdentityTranslator;
use Dmytrof\ImportBundle\Tests\Fixtures\Reader\MockedJsonReader;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class JsonReaderTest extends TestCase
{
    private function reader(Response ...$responses): MockedJsonReader
    {
        return (new MockedJsonReader(new IdentityTranslator()))->queueResponses($responses);
    }

    public function testDefinition(): void
    {
        $this->assertSame('json', JsonReader::getCode());
        $this->assertFalse(JsonReader::hasOptions());
        $this->assertFalse(JsonReader::isDataInRoot());
        $this->assertSame('dmytrof.reader.json.label', (new JsonReader(new IdentityTranslator()))->getTitle());
    }

    public function testReadsJson(): void
    {
        $reader = $this->reader(new Response(200, ['Content-Type' => 'application/json'], '{"items":[{"sku":"A1"},{"sku":"B2"}]}'));

        $data = $reader->getDataFromLink('https://example.com/feed.json');

        $this->assertInstanceOf(ImportedData::class, $data);
        $this->assertSame(['items' => [['sku' => 'A1'], ['sku' => 'B2']]], $data->getData());
        $this->assertSame('https://example.com/feed.json', (string) $reader->getRequestHistory()[0]['request']->getUri());
    }

    public function testInvalidJson(): void
    {
        $this->expectException(ReaderException::class);
        $this->expectExceptionMessage('Invalid JSON data');

        $this->reader(new Response(200, [], '{broken'))->getDataFromLink('https://example.com');
    }

    public function testNotFound(): void
    {
        $this->expectException(ReaderException::class);
        $this->expectExceptionMessage('Resource not found');

        $this->reader(new Response(404))->getDataFromLink('https://example.com');
    }

    public function testNonOkStatus(): void
    {
        $this->expectException(ReaderException::class);
        $this->expectExceptionMessage('Response status is not OK: 204');

        $this->reader(new Response(204))->getDataFromLink('https://example.com');
    }

    public function testLoadAttemptsMustBeInteger(): void
    {
        $this->expectException(\Symfony\Component\OptionsResolver\Exception\InvalidOptionsException::class);

        $this->reader(new Response(200, [], '{}'))->getDataFromLink('https://example.com', ['loadAttempts' => '3']);
    }
}
