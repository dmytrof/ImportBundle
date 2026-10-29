<?php

namespace Dmytrof\ImportBundle\Tests\Unit\Importer;

use Dmytrof\ImportBundle\Exception\ImporterException;
use Dmytrof\ImportBundle\Model\{ImportFormData, Item, Task};
use Dmytrof\ImportBundle\Tests\Fixtures\{CreatesTasksTrait, TestImporter};
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Style\SymfonyStyle;

class AbstractImporterTest extends TestCase
{
    use CreatesTasksTrait;

    private Task $task;
    private TestImporter $importer;

    protected function setUp(): void
    {
        $this->task = $this->createTask()->setImporterCode(TestImporter::CODE);
        $this->importer = $this->task->getImporter();
    }

    private function fieldOptions(string $field)
    {
        return $this->task->getImporterOptions()->getImportableFieldsOptions()->get($field);
    }

    private function item(array $data): Item
    {
        return (new Item())->setTaskId(1)->setEntryId('e')->setData($data);
    }

    public function testStaticDefinition(): void
    {
        $this->assertSame('test', TestImporter::getCode());
        $this->assertTrue(TestImporter::hasOptions());
        $this->assertSame(10, TestImporter::getBatchLength());
        $this->assertSame('Test importer', $this->importer->getTitle());
    }

    public function testDefaultOutputAndLogger(): void
    {
        $this->assertInstanceOf(SymfonyStyle::class, $this->importer->getOutput());
        $this->assertInstanceOf(NullLogger::class, $this->importer->getLogger());
    }

    public function testManagerIsRequired(): void
    {
        $this->expectException(ImporterException::class);
        $this->importer->getManager();
    }

    public function testEntryIdIsHashOfConfiguredFields(): void
    {
        $this->task->getImporterOptions()->setItemHashIdFields(['sku', 'meta/lang']);

        $this->assertSame(
            md5('A1-en'),
            $this->importer->entryId(['sku' => 'A1', 'meta' => ['lang' => 'en'], 'name' => 'ignored'])
        );
    }

    public function testEntryIdEncodesNonScalarValues(): void
    {
        $this->task->getImporterOptions()->setItemHashIdFields(['tags']);

        $this->assertSame(md5(json_encode(['a', 'b'])), $this->importer->entryId(['tags' => ['a', 'b']]));
        $this->assertSame(md5('null'), $this->importer->entryId([]), 'missing value is encoded as null');
    }

    public function testImportFormDataMapsKeysAndPaths(): void
    {
        $this->fieldOptions('title')->setKey('name');
        $this->fieldOptions('price')->setKey('pricing/amount');
        $this->fieldOptions('address.city')->setKey('location/city');

        $formData = $this->importer->getImportFormData($this->item([
            'name' => '  Apple  ',
            'pricing' => ['amount' => 1.5],
            'location' => ['city' => 'Kyiv'],
        ]));

        $this->assertInstanceOf(ImportFormData::class, $formData);
        $this->assertSame([
            'title' => 'Apple',
            'price' => 1.5,
            'address' => ['city' => 'Kyiv'],
        ], $formData->getData());
    }

    public function testFieldWithoutKeyUsesItsOwnName(): void
    {
        $formData = $this->importer->getImportFormData($this->item(['title' => 'Apple', 'price' => 2]));

        $this->assertSame('Apple', $formData['title']);
        $this->assertSame(2, $formData['price']);
    }

    public function testFallbackKeyIsUsedWhenKeyValueIsEmpty(): void
    {
        $this->fieldOptions('title')->setKey('name')->setFallbackKey('title_en');

        $this->assertSame('Apple', $this->importer->getImportFormData($this->item(['name' => '', 'title_en' => 'Apple']))['title']);
        $this->assertSame('Pear', $this->importer->getImportFormData($this->item(['name' => 'Pear', 'title_en' => 'Apple']))['title']);
    }

    public function testDefaultValueIsUsedWhenValueIsEmpty(): void
    {
        $this->fieldOptions('title')->setKey('name')->setDefaultValue('Untitled');

        $this->assertSame('Untitled', $this->importer->getImportFormData($this->item(['name' => null]))['title']);
        $this->assertSame('Apple', $this->importer->getImportFormData($this->item(['name' => 'Apple']))['title']);
    }

    public function testDataFromImportFormDataIsWrittenBackToSourceKeys(): void
    {
        $this->fieldOptions('title')->setKey('name');
        $this->fieldOptions('address.city')->setKey('city');
        $item = $this->item(['name' => 'Apple', 'city' => 'Lviv', 'extra' => 'kept']);

        $data = $this->importer->getDataFromImportFormData(
            new ImportFormData(['title' => 'Green apple', 'address' => ['city' => 'Kyiv']]),
            $item
        );

        $this->assertSame(['name' => 'Green apple', 'city' => 'Kyiv', 'extra' => 'kept'], $data);
    }

    public function testImportDataWithoutDataFails(): void
    {
        $this->expectException(ImporterException::class);
        $this->importer->importData(new \Dmytrof\ImportBundle\Model\ImportedData([]));
    }
}
