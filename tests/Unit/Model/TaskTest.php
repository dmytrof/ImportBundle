<?php

namespace Dmytrof\ImportBundle\Tests\Unit\Model;

use Dmytrof\ImportBundle\Exception\TaskException;
use Dmytrof\ImportBundle\Importer\Options\ImporterOptions;
use Dmytrof\ImportBundle\Model\{ImporterDefinition, ReaderDefinition, Task};
use Dmytrof\ImportBundle\Reader\Options\CsvReaderOptions;
use Dmytrof\ImportBundle\Tests\Fixtures\{CreatesTasksTrait, TestImporter};
use PHPUnit\Framework\TestCase;

class TaskTest extends TestCase
{
    use CreatesTasksTrait;

    public function testDefaults(): void
    {
        $task = new Task();

        $this->assertFalse($task->isInProgress());
        $this->assertTrue($task->isActive());
        $this->assertFalse($task->isPaginatedLink());
        $this->assertSame(Task::PAGE_PARAMETER_IN_LINK, $task->getPageParameterInLink());
        $this->assertSame(1, $task->getFirstPageValue());
        $this->assertFalse($task->isForceImport());
    }

    public function testCloneResetsProgressAndActivity(): void
    {
        $task = (new Task())->setInProgress(true);
        $task->setActive(false);

        $clone = clone $task;

        $this->assertFalse($clone->isInProgress());
        $this->assertTrue($clone->isActive());
    }

    public function testPeriods(): void
    {
        $this->assertSame(Task::PERIOD_CHOICES, Task::getPeriods());
        $this->assertSame(Task::PERIODS, Task::getPeriodsTitles());
    }

    public function testPreparedLinkWithoutPagination(): void
    {
        $task = (new Task())->setLink('https://example.com/feed.json');

        $this->assertSame('https://example.com/feed.json', $task->getPreparedLink(5));
    }

    public function testPreparedLinkWithPagination(): void
    {
        $task = (new Task())
            ->setLink('https://example.com/feed.json?page={page}')
            ->setPaginatedLink(true)
        ;

        $this->assertSame('https://example.com/feed.json?page=3', $task->getPreparedLink(3));
    }

    public function testPreparedLinkWithCustomPageParameter(): void
    {
        $task = (new Task())
            ->setLink('https://example.com/feed/PAGE')
            ->setPaginatedLink(true)
            ->setPageParameterInLink('PAGE')
        ;

        $this->assertSame('https://example.com/feed/2', $task->getPreparedLink(2));
    }

    public function testPaginatedLinkWithoutPageParameterThrows(): void
    {
        $task = (new Task())
            ->setLink('https://example.com/feed.json')
            ->setPaginatedLink(true)
        ;

        $this->expectException(TaskException::class);
        $task->getPreparedLink(1);
    }

    public function testPreparedLinkReplacesLinkParams(): void
    {
        $task = (new Task())->setLink('https://example.com/{lang}/feed?category={category}');

        $this->assertSame(
            'https://example.com/en/feed?category=books',
            $task->getPreparedLink(1, ['lang' => 'en', 'category' => 'books'])
        );
    }

    public function testModelTitle(): void
    {
        $this->assertSame('Feed (ID: )', (new Task())->setTitle('Feed')->getModelTitle());
    }

    public function testSettingImporterCodeWithoutContainerThrows(): void
    {
        $this->expectException(TaskException::class);
        (new Task())->setImporterCode(TestImporter::CODE);
    }

    public function testImporterIsClonedFromContainerAndBoundToTask(): void
    {
        $task = $this->createTask()->setImporterCode(TestImporter::CODE);

        $importer = $task->getImporter();
        $this->assertInstanceOf(TestImporter::class, $importer);
        $this->assertSame($task, $importer->getTask());
        $this->assertNotSame($this->importersContainer->get(TestImporter::CODE), $importer);
        $this->assertSame('Test importer', $task->getImporterTitle());
    }

    public function testImporterOptionsArePreparedForImportableFields(): void
    {
        $task = $this->createTask()->setImporterCode(TestImporter::CODE);

        $options = $task->getImporterOptions();
        $this->assertInstanceOf(ImporterOptions::class, $options);
        $this->assertSame(['title', 'price', 'address.city'], $options->getImportableFieldsOptions()->getKeys());
    }

    public function testChangingImporterOptionsUpdatesTaskArray(): void
    {
        $task = $this->createTask()->setImporterCode(TestImporter::CODE);
        $options = $task->getImporterOptions();

        $options->setDataPath('items');
        $this->assertSame('items', $task->getImporterOptionsArr()['dataPath']);

        // Nested observers: field options -> fields options -> importer options -> task
        $options->getImportableFieldsOptions()->get('title')->setKey('name');
        $this->assertSame('name', $task->getImporterOptionsArr()['importableFieldsOptions']['title']['key']);
    }

    public function testImporterOptionsHashDependsOnHashSchemeOnly(): void
    {
        $task = $this->createTask()->setImporterCode(TestImporter::CODE);
        $options = $task->getImporterOptions();
        $options->setItemHashIdFields(['sku']);
        $hash = $task->getImporterOptionsHash();

        $options->setDataPath('other/path');
        $this->assertSame($hash, $task->getImporterOptionsHash(), 'dataPath is not part of the hash scheme');

        $options->setItemHashIdFields(['id']);
        $this->assertNotSame($hash, $task->getImporterOptionsHash());
    }

    public function testImporterOptionsAreRestoredFromArray(): void
    {
        $source = $this->createTask()->setImporterCode(TestImporter::CODE);
        $source->getImporterOptions()->setDataPath('data/items')->setItemHashIdFields(['sku']);
        $source->getImporterOptions()->getImportableFieldsOptions()->get('price')->setKey('cost');

        $task = $this->createTask();
        $task->setImporterOptionsArr($source->getImporterOptionsArr());
        // Re-select importer without resetting the array (what Doctrine hydration does)
        (new \ReflectionProperty(Task::class, 'importerCode'))->setValue($task, TestImporter::CODE);

        $options = $task->getImporterOptions();
        $this->assertSame('data/items', $options->getDataPath());
        $this->assertSame(['sku'], $options->getItemHashIdFields());
        $this->assertSame('cost', $options->getImportableFieldsOptions()->get('price')->getKey());
    }

    public function testReaderOptions(): void
    {
        $task = $this->createTask()->setReaderCode('csv');

        $this->assertSame('csv', $task->getReader()::getCode());
        $options = $task->getReaderOptions();
        $this->assertInstanceOf(CsvReaderOptions::class, $options);

        $options->setDelimiter(';');
        $this->assertSame(';', $task->getReaderOptionsArr()['delimiter']);
    }

    public function testReaderWithoutOptions(): void
    {
        $task = $this->createTask()->setReaderCode('json');

        $this->assertNull($task->getReaderOptions());
        $this->assertNull($task->getReaderOptionsArr());
    }

    public function testUnknownReader(): void
    {
        $task = $this->createTask()->setReaderCode('unknown');

        $this->assertNull($task->getReader());
        $this->expectException(TaskException::class);
        $task->getDataFromLink('https://example.com');
    }

    public function testDefinitions(): void
    {
        $task = $this->createTask()->setImporterCode(TestImporter::CODE)->setReaderCode('csv');

        $importerDefinition = $task->getImporterDefinition();
        $this->assertInstanceOf(ImporterDefinition::class, $importerDefinition);
        $this->assertSame(TestImporter::CODE, $importerDefinition->getCode());
        $this->assertSame($task->getImporterOptions(), $importerDefinition->{TestImporter::CODE});

        $readerDefinition = $task->getReaderDefinition();
        $this->assertInstanceOf(ReaderDefinition::class, $readerDefinition);
        $this->assertSame('csv', $readerDefinition->getCode());
    }
}
