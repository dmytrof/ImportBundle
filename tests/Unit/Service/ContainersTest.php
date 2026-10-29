<?php

namespace Dmytrof\ImportBundle\Tests\Unit\Service;

use Dmytrof\ImportBundle\Exception\{ImporterException, ReaderException};
use Dmytrof\ImportBundle\Manager\ItemManager;
use Dmytrof\ImportBundle\Service\{ImportersContainer, ReadersContainer};
use Dmytrof\ImportBundle\Tests\Fixtures\{CreatesTasksTrait, IdentityTranslator, TestImporter};
use PHPUnit\Framework\TestCase;

class ContainersTest extends TestCase
{
    use CreatesTasksTrait;

    public function testImportersContainer(): void
    {
        $container = $this->createImportersContainer();

        $this->assertTrue($container->has('test'));
        $this->assertFalse($container->has('missing'));
        $this->assertSame(['test'], $container->getImportersCodes());
        $this->assertSame(['test' => 'Test importer'], $container->getImportersTitles());
        $this->assertCount(1, iterator_to_array($container));
    }

    public function testImportersContainerInjectsDependencies(): void
    {
        $importer = new TestImporter();
        $translator = new IdentityTranslator();
        $itemManager = $this->createStub(ItemManager::class);

        new ImportersContainer($translator, $itemManager, [$importer]);

        $this->assertSame($translator, $importer->getTranslator());
        $this->assertSame($itemManager, $importer->getItemManager());
    }

    public function testImportersContainerKeepsPresetDependencies(): void
    {
        $translator = new IdentityTranslator();
        $importer = (new TestImporter())->setTranslator($translator);

        new ImportersContainer(new IdentityTranslator(), $this->createStub(ItemManager::class), [$importer]);

        $this->assertSame($translator, $importer->getTranslator());
    }

    public function testUnknownImporter(): void
    {
        $this->expectException(ImporterException::class);
        $this->createImportersContainer()->get('missing');
    }

    public function testReadersContainer(): void
    {
        $container = $this->createReadersContainer();

        $this->assertSame(['json', 'xml', 'rss', 'csv'], $container->getReadersCodes());
        $this->assertSame('dmytrof.reader.csv.label', $container->getReadersTitles()['csv']);
        $this->assertSame('csv', $container->get('csv')::getCode());
        $this->assertFalse($container->has(null));
        $this->assertCount(4, iterator_to_array($container));
    }

    public function testUnknownReader(): void
    {
        $this->expectException(ReaderException::class);
        (new ReadersContainer([]))->get('csv');
    }
}
