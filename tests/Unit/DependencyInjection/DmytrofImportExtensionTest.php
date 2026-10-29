<?php

namespace Dmytrof\ImportBundle\Tests\Unit\DependencyInjection;

use Dmytrof\ImportBundle\Command\{ImportItemsCommand, ImportTasksCommand};
use Dmytrof\ImportBundle\DependencyInjection\{Configuration, DmytrofImportExtension};
use Dmytrof\ImportBundle\EventListener\ImportDoctrineSubscriber;
use Dmytrof\ImportBundle\Importer\ImporterInterface;
use Dmytrof\ImportBundle\Manager\{ItemManager, TaskManager};
use Dmytrof\ImportBundle\Reader\{CsvReader, JsonReader, ReaderInterface, RssReader, XmlReader};
use Dmytrof\ImportBundle\Service\{ImportersContainer, Logger, ReadersContainer};
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\Argument\TaggedIteratorArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class DmytrofImportExtensionTest extends TestCase
{
    private ContainerBuilder $container;

    protected function setUp(): void
    {
        $this->container = new ContainerBuilder();
        (new DmytrofImportExtension())->load([], $this->container);
    }

    public function testDefaultConfiguration(): void
    {
        $config = (new Processor())->processConfiguration(new Configuration(), []);
        $this->assertSame(['log_dir' => '%kernel.logs_dir%/import'], $config);

        $config = (new Processor())->processConfiguration(new Configuration(), [['log_dir' => '/var/log/imports']]);
        $this->assertSame('/var/log/imports', $config['log_dir']);
    }

    public function testParameters(): void
    {
        $this->assertSame('%kernel.logs_dir%/import', $this->container->getParameter('dmytrof_import.log_dir'));
    }

    public function testServicesAreRegistered(): void
    {
        foreach ([
            ImportTasksCommand::class, ImportItemsCommand::class,
            TaskManager::class, ItemManager::class,
            ImportersContainer::class, ReadersContainer::class,
            JsonReader::class, XmlReader::class, RssReader::class, CsvReader::class,
            Logger::class, ImportDoctrineSubscriber::class,
        ] as $id) {
            $this->assertTrue($this->container->hasDefinition($id), "$id is registered");
        }
        $this->assertTrue($this->container->getDefinition(TaskManager::class)->isPublic());
        $this->assertTrue($this->container->getDefinition(ItemManager::class)->isPublic());
    }

    public function testNonServiceDirectoriesAreExcluded(): void
    {
        foreach (array_keys($this->container->getDefinitions()) as $id) {
            $this->assertDoesNotMatchRegularExpression(
                '/^Dmytrof\\\\ImportBundle\\\\(Entity|Event|Model|Exception|DependencyInjection)\\\\/',
                $id
            );
        }
    }

    public function testDoctrineListenerTag(): void
    {
        $this->assertSame(
            [['event' => 'postLoad']],
            $this->container->getDefinition(ImportDoctrineSubscriber::class)->getTag('doctrine.event_listener')
        );
        $this->assertFalse($this->container->getDefinition(ImportDoctrineSubscriber::class)->hasTag('doctrine.event_subscriber'));
    }

    public function testTaggedIterators(): void
    {
        $importers = $this->container->getDefinition(ImportersContainer::class)->getArgument('$importers');
        $readers = $this->container->getDefinition(ReadersContainer::class)->getArgument('$readers');

        $this->assertInstanceOf(TaggedIteratorArgument::class, $importers);
        $this->assertSame('dmytrof.import.importer', $importers->getTag());
        $this->assertInstanceOf(TaggedIteratorArgument::class, $readers);
        $this->assertSame('dmytrof.import.reader', $readers->getTag());
    }

    public function testAutoconfiguration(): void
    {
        $autoconfigured = $this->container->getAutoconfiguredInstanceof();

        $this->assertArrayHasKey('dmytrof.import.importer', $autoconfigured[ImporterInterface::class]->getTags());
        $this->assertArrayHasKey('dmytrof.import.reader', $autoconfigured[ReaderInterface::class]->getTags());
    }
}
