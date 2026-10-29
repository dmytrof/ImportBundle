<?php

namespace Dmytrof\ImportBundle\Tests\Unit\Model;

use Dmytrof\ImportBundle\Model\{ImportedData, ImportedDataFile};
use PHPUnit\Framework\TestCase;

class ImportedDataTest extends TestCase
{
    private array $files = [];

    protected function tearDown(): void
    {
        array_map('unlink', array_filter($this->files, 'is_file'));
    }

    public function testInMemoryData(): void
    {
        $data = (new ImportedData([['a' => 1], ['a' => 2]]))->setHeadingRow(['a']);

        $this->assertSame([['a' => 1], ['a' => 2]], $data->getData());
        $this->assertSame($data->getData(), $data->getExampleData());
        $this->assertTrue($data->hasHeadingRow());
        $this->assertSame(['a' => 1], $data->prepareRow(['a' => 1]));
        $this->assertSame([
            'data' => [['a' => 1], ['a' => 2]],
            'headingRow' => ['a'],
            'dataInRoot' => false,
        ], $data->getExampleDataParams());
    }

    public function testCountDependsOnDataInRoot(): void
    {
        $data = new ImportedData([1, 2, 3]);
        $this->assertCount(0, $data, 'Nested data cannot be counted until the data path is applied');

        $data->setDataInRoot(true);
        $this->assertCount(3, $data);
    }

    public function testFileData(): void
    {
        $data = new ImportedDataFile($this->files[] = tempnam(sys_get_temp_dir(), 'imp'));
        $data->addRow(['sku' => 'A1', 'name' => 'Яблуко'], true);
        for ($i = 2; $i <= 25; $i++) {
            $data->addRow(['sku' => 'A'.$i], false);
        }

        $this->assertTrue($data->isDataInRoot());
        $this->assertCount(25, $data);

        $rows = [];
        foreach ($data->getData() as $row) {
            $rows[] = $data->prepareRow($row);
        }
        $this->assertCount(25, $rows);
        $this->assertSame(['sku' => 'A1', 'name' => 'Яблуко'], $rows[0]);

        $this->assertCount(20, $data->getExampleData(), 'Example data is limited to 20 rows');
    }
}
