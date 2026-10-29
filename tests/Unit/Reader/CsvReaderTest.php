<?php

namespace Dmytrof\ImportBundle\Tests\Unit\Reader;

use Dmytrof\ImportBundle\Exception\ReaderException;
use Dmytrof\ImportBundle\Model\{ImportedDataFile, Task};
use Dmytrof\ImportBundle\Reader\CsvReader;
use Dmytrof\ImportBundle\Reader\Options\CsvReaderOptions;
use Dmytrof\ImportBundle\Tests\Fixtures\{CreatesTasksTrait, IdentityTranslator};
use Dmytrof\ImportBundle\Tests\Fixtures\Reader\MockedCsvReader;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class CsvReaderTest extends TestCase
{
    use CreatesTasksTrait;

    private MockedCsvReader $reader;
    private Task $task;

    protected function setUp(): void
    {
        $this->reader = new MockedCsvReader(new IdentityTranslator());
        $this->task = $this->createTask();
        $this->task->setReadersContainer($this->createReadersContainer([$this->reader]))->setReaderCode('csv');
    }

    private function read(string $csv, string $contentType = 'text/csv', array $options = []): ImportedDataFile
    {
        // Task::getReader() works with a clone of the container reader
        $this->task->getReader()->queueResponses([new Response(200, ['Content-Type' => $contentType], $csv)]);

        return $this->task->getDataFromLink('https://example.com/data.csv', $options);
    }

    private function rows(ImportedDataFile $data): array
    {
        $rows = [];
        foreach ($data->getData() as $row) {
            $rows[] = $data->prepareRow($row);
        }

        return $rows;
    }

    public function testDefinition(): void
    {
        $this->assertSame('csv', CsvReader::getCode());
        $this->assertSame(CsvReaderOptions::class, CsvReader::getOptionsClass());
        $this->assertTrue(CsvReader::isDataInRoot());
    }

    public function testDefaultOptions(): void
    {
        $options = $this->task->getReaderOptions();

        $this->assertFalse($options->getHasHeadingRow());
        $this->assertTrue($options->isSkipEmptyRows());
        $this->assertSame(',', $options->getDelimiter());
        $this->assertSame('"', $options->getEnclosure());
        $this->assertSame('\\', $options->getEscape());
    }

    public function testReadsRowsAndSkipsEmptyOnes(): void
    {
        $data = $this->read("A1,Apple,\"1,5\"\n,,\nB2,Banana,2\n");

        $this->assertNull($data->getHeadingRow());
        $this->assertSame([['A1', 'Apple', '1,5'], ['B2', 'Banana', '2']], $this->rows($data));
    }

    public function testHeadingRowAndCustomDelimiter(): void
    {
        $this->task->getReaderOptions()->setHasHeadingRow(true)->setDelimiter(';');

        $data = $this->read("sku;name\nA1;Яблуко\n");

        $this->assertSame(['sku', 'name'], $data->getHeadingRow());
        $this->assertSame([['A1', 'Яблуко']], $this->rows($data));
    }

    public function testExampleDataIsLimited(): void
    {
        $data = $this->read(implode("\n", array_map(fn ($i) => "row$i,x", range(1, 150))), 'text/csv', ['exampleData' => true]);

        $this->assertCount(101, $this->rows($data));
    }

    public static function contentTypes(): iterable
    {
        yield ['text/csv'];
        yield ['application/octet-stream'];
        yield ['text/csv; charset=UTF-8'];
        yield ['TEXT/CSV;charset=utf-8'];
    }

    #[DataProvider('contentTypes')]
    public function testAcceptedContentTypes(string $contentType): void
    {
        $this->assertSame([['a', 'b']], $this->rows($this->read("a,b\n", $contentType)));
    }

    public function testRejectsOtherContentTypes(): void
    {
        $this->expectException(ReaderException::class);
        $this->expectExceptionMessage('Invalid CSV data');

        $this->read('{"a":1}', 'application/json');
    }
}
