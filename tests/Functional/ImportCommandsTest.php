<?php

namespace Dmytrof\ImportBundle\Tests\Functional;

use Dmytrof\ImportBundle\Entity\Item\Item as ItemEntity;
use Dmytrof\ImportBundle\Entity\Task\Task as TaskEntity;
use Dmytrof\ImportBundle\Event\{PostImportTaskEvent, PreImportTaskEvent};
use Dmytrof\ImportBundle\Exception\TaskNotFoundException;
use Dmytrof\ImportBundle\Manager\{ItemManager, TaskManager};
use Dmytrof\ImportBundle\Model\Item;
use Dmytrof\ImportBundle\Service\{ImportersContainer, ReadersContainer};
use Dmytrof\ImportBundle\Tests\Functional\App\Entity\Product;
use Dmytrof\ImportBundle\Tests\Functional\App\Importer\ProductImporter;
use Dmytrof\ImportBundle\Tests\Functional\App\Reader\FixtureFileReader;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ImportCommandsTest extends KernelTestCase
{
    private const DATA_DIR = __DIR__.'/App/data';

    private EntityManagerInterface $em;
    private TaskManager $taskManager;
    private ItemManager $itemManager;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $this->em = $container->get('doctrine.orm.entity_manager');
        $this->taskManager = $container->get(TaskManager::class);
        $this->itemManager = $container->get(ItemManager::class);

        $metadata = $this->em->getMetadataFactory()->getAllMetadata();
        $tool = new SchemaTool($this->em);
        $tool->dropSchema($metadata);
        $tool->createSchema($metadata);
    }

    private function createTask(string $file = 'products.json', array $extra = []): TaskEntity
    {
        $container = static::getContainer();
        /** @var TaskEntity $task */
        $task = $this->taskManager->new();
        $task
            ->setImportersContainer($container->get(ImportersContainer::class))
            ->setReadersContainer($container->get(ReadersContainer::class))
            ->setTitle('Products feed')
            ->setLink(self::DATA_DIR.'/'.$file)
            ->setReaderCode(FixtureFileReader::CODE)
            ->setImporterCode(ProductImporter::CODE)
            ->setPeriod(TaskEntity::PERIOD_EVERY_HOUR)
        ;
        foreach ($extra as $setter => $value) {
            $task->$setter($value);
        }
        $options = $task->getImporterOptions();
        $options->setDataPath('items')->setItemHashIdFields(['sku']);
        $options->getImportableFieldsOptions()->get('title')->setKey('name');
        $options->getImportableFieldsOptions()->get('price')->setKey('cost');

        $this->taskManager->save($task);
        $this->em->clear();

        return $task;
    }

    private function executeCommand(string $name, array $input = []): CommandTester
    {
        $application = new Application(self::$kernel);
        $tester = new CommandTester($application->find($name));
        $tester->execute($input);
        $this->em->clear();

        return $tester;
    }

    /**
     * @return array<string, float|null> title => price
     */
    private function products(): array
    {
        $products = [];
        foreach ($this->em->getRepository(Product::class)->findBy([], ['id' => 'ASC']) as $product) {
            $products[$product->getTitle()] = $product->getPrice();
        }

        return $products;
    }

    /**
     * @return array<string, int> status title => count
     */
    private function itemStatuses(): array
    {
        $statuses = [];
        foreach ($this->em->getRepository(ItemEntity::class)->findAll() as $item) {
            $statuses[$item->getStatusTitle()] = ($statuses[$item->getStatusTitle()] ?? 0) + 1;
        }
        ksort($statuses);

        return $statuses;
    }

    public function testCommandsAreRegistered(): void
    {
        $application = new Application(self::$kernel);

        $this->assertSame('Doing import of tasks.', $application->find('dmytrof:import:tasks')->getDescription());
        $this->assertSame('Doing import of scheduled items.', $application->find('dmytrof:import:items')->getDescription());
    }

    public function testBundleServicesAreAvailable(): void
    {
        $container = static::getContainer();

        $this->assertSame([ProductImporter::CODE], $container->get(ImportersContainer::class)->getImportersCodes());
        $this->assertEqualsCanonicalizing(
            ['json', 'xml', 'rss', 'csv', FixtureFileReader::CODE],
            $container->get(ReadersContainer::class)->getReadersCodes()
        );
    }

    public function testLoadedTaskGetsServicesInjected(): void
    {
        $id = $this->createTask()->getId();

        $task = $this->taskManager->getTask($id);

        $this->assertNotNull($task->getImportersContainer());
        $this->assertNotNull($task->getReadersContainer());
        $this->assertNotNull($task->getEventDispatcher());
        $this->assertInstanceOf(ProductImporter::class, $task->getImporter());
        $this->assertSame('name', $task->getImporterOptions()->getImportableFieldsOptions()->get('title')->getKey());
    }

    public function testUniqueTaskCode(): void
    {
        $this->createTask(extra: ['setCode' => 'products']);
        $duplicate = $this->taskManager->new()->setTitle('Duplicate')->setCode('products');

        $violations = static::getContainer()->get('validator')->validate($duplicate);

        $this->assertContains('code', array_map(fn ($v) => $v->getPropertyPath(), iterator_to_array($violations)));
    }

    public function testImportScheduledTasks(): void
    {
        $id = $this->createTask()->getId();
        $events = [];
        $dispatcher = static::getContainer()->get(EventDispatcherInterface::class);
        $dispatcher->addListener(PreImportTaskEvent::class, function (PreImportTaskEvent $e) use (&$events) { $events[] = 'pre:'.$e->getTask()->getId(); });
        $dispatcher->addListener(PostImportTaskEvent::class, function (PostImportTaskEvent $e) use (&$events) { $events[] = 'post:'.$e->getTask()->getId(); });

        $tester = $this->executeCommand('dmytrof:import:tasks');

        $tester->assertCommandIsSuccessful();
        $output = $tester->getDisplay();
        $this->assertStringContainsString('There are 1 scheduled tasks', $output);
        $this->assertStringContainsString('Importing finished!', $output);

        $this->assertSame(['Apple' => 1.5, 'Banana' => 0.25], $this->products());
        $this->assertSame([
            'label.import_item.statuses.created' => 2,
            'label.import_item.statuses.data_error' => 1,
        ], $this->itemStatuses());

        $error = $this->em->getRepository(ItemEntity::class)->findOneBy(['statusId' => Item::STATUS_DATA_ERROR]);
        $this->assertSame(['title' => ['This value should not be blank.']], $error->getErrors());
        $this->assertSame(['sku' => 'C3', 'name' => '', 'cost' => '3'], $error->getData());

        $task = $this->taskManager->getTask($id);
        $this->assertFalse($task->isInProgress());
        $this->assertNotNull($task->getImportedAt());
        $this->assertSame(["pre:$id", "post:$id"], $events);
    }

    public function testImportedTaskIsNotScheduledUntilPeriodPasses(): void
    {
        $this->createTask();
        $this->createTask(extra: ['setActive' => false]);
        $this->createTask(extra: ['setPeriod' => null]);
        $this->assertCount(1, $this->taskManager->getScheduledImportTasks());

        $this->executeCommand('dmytrof:import:tasks');

        $this->assertCount(0, $this->taskManager->getScheduledImportTasks());
        $this->assertStringContainsString('No scheduled tasks for now', $this->executeCommand('dmytrof:import:tasks')->getDisplay());
    }

    public function testSecondImportSkipsUnchangedEntries(): void
    {
        $id = $this->createTask()->getId();
        $this->executeCommand('dmytrof:import:tasks', ['--task' => (string) $id]);

        $tester = $this->executeCommand('dmytrof:import:tasks', ['--task' => (string) $id]);

        $this->assertMatchesRegularExpression('/^\s*3\s+2\s+0\s+0\s+0\s+0\s+1\s*$/m', $tester->getDisplay(), 'All | Skipped | Scheduled | Created | Updated | Duplicates | Errors');
        $this->assertSame(['Apple' => 1.5, 'Banana' => 0.25], $this->products());
        $this->assertSame([
            'label.import_item.statuses.data_error' => 1,
            'label.import_item.statuses.skipped' => 2,
        ], $this->itemStatuses());
    }

    public function testForceImportReimportsUnchangedEntries(): void
    {
        $id = $this->createTask()->getId();
        $this->executeCommand('dmytrof:import:tasks', ['--task' => (string) $id]);

        $task = $this->taskManager->getTask($id);
        $task->getImporterOptions()->setForce(true);
        $this->taskManager->save($task);
        $this->em->clear();

        $tester = $this->executeCommand('dmytrof:import:tasks', ['--task' => (string) $id]);

        $this->assertMatchesRegularExpression('/^\s*3\s+0\s+0\s+0\s+2\s+0\s+1\s*$/m', $tester->getDisplay());
        $this->assertCount(2, $this->products(), 'existing products are updated, not duplicated');
    }

    public function testChangedEntriesAreUpdatedUsingLinkParams(): void
    {
        $id = $this->createTask('{file}.json')->getId();
        $this->executeCommand('dmytrof:import:tasks', ['--task' => (string) $id, '--link-params' => '{"file":"products"}']);

        $tester = $this->executeCommand('dmytrof:import:tasks', ['--task' => (string) $id, '--link-params' => '{"file":"products_v2"}']);

        $tester->assertCommandIsSuccessful();
        $this->assertStringContainsString('Link params: {"file":"products_v2"}', $tester->getDisplay());
        $this->assertSame(['Green apple' => 1.75, 'Banana' => 0.25, 'Cherry' => 3.0], $this->products());
        $this->assertSame([
            'label.import_item.statuses.created' => 1,   // C3 is valid now, product created
            'label.import_item.statuses.duplicate' => 1, // second B2 row
            'label.import_item.statuses.skipped' => 1,   // B2 unchanged
            'label.import_item.statuses.updated' => 1,   // A1 changed
        ], $this->itemStatuses());
    }

    public function testPaginatedImportStopsOnEmptyPage(): void
    {
        $id = $this->createTask('page{page}.json', ['setPaginatedLink' => true])->getId();

        $tester = $this->executeCommand('dmytrof:import:tasks', ['--task' => (string) $id]);

        $tester->assertCommandIsSuccessful();
        $this->assertStringContainsString('(Page 3)', $tester->getDisplay());
        $this->assertSame(['Page one product' => 1.0, 'Page two product' => 2.0], $this->products());
    }

    public function testSelectedPages(): void
    {
        $id = $this->createTask('page{page}.json', ['setPaginatedLink' => true])->getId();

        $this->executeCommand('dmytrof:import:tasks', ['--task' => (string) $id, '--page' => '2']);

        $this->assertSame(['Page two product' => 2.0], $this->products());
    }

    public function testUnknownTask(): void
    {
        $tester = $this->executeCommand('dmytrof:import:tasks', ['--task' => '999']);
        $tester->assertCommandIsSuccessful();
        $this->assertStringContainsString('Import task "999" error: Task not found', $tester->getDisplay());

        $this->expectException(TaskNotFoundException::class);
        $this->executeCommand('dmytrof:import:tasks', ['--task' => '999', '--throw-exceptions' => true]);
    }

    public function testLockIsReleasedWhenImportFails(): void
    {
        try {
            $this->executeCommand('dmytrof:import:tasks', ['--task' => '999', '--throw-exceptions' => true]);
            $this->fail('Exception expected');
        } catch (TaskNotFoundException) {
        }

        $display = $this->executeCommand('dmytrof:import:tasks')->getDisplay();
        $this->assertStringNotContainsString('already running', $display);
        $this->assertStringContainsString('No scheduled tasks for now', $display);
    }

    private function scheduleErroneousItemWithFixedData(): string
    {
        /** @var ItemEntity $item */
        $item = $this->em->getRepository(ItemEntity::class)->findOneBy(['statusId' => Item::STATUS_DATA_ERROR]);
        $this->em->getConnection()->update('dmytrof_import_item', [
            'data' => json_encode(['sku' => 'C3', 'name' => 'Cherry', 'cost' => '3']),
            'status_id' => Item::STATUS_SCHEDULED,
        ], ['id' => $item->getId()]);
        $this->em->clear();

        return $item->getId();
    }

    public function testImportSingleItemOption(): void
    {
        $id = $this->createTask()->getId();
        $this->executeCommand('dmytrof:import:tasks', ['--task' => (string) $id]);
        $itemId = $this->scheduleErroneousItemWithFixedData();

        $tester = $this->executeCommand('dmytrof:import:items', ['--item' => $itemId]);

        $tester->assertCommandIsSuccessful();
        $this->assertStringContainsString('Imported! Status: label.import_item.statuses.created', $tester->getDisplay());
        $this->assertArrayHasKey('Cherry', $this->products());
        $this->assertSame(Item::STATUS_CREATED, $this->itemManager->get($itemId)->getStatusId());
    }

    public function testImportScheduledItems(): void
    {
        $id = $this->createTask()->getId();
        $this->executeCommand('dmytrof:import:tasks', ['--task' => (string) $id]);

        // Fix the invalid entry's data and schedule it for re-import
        $itemId = $this->scheduleErroneousItemWithFixedData();
        $this->assertSame([$itemId], $this->itemManager->getScheduledImportItems());

        $tester = $this->executeCommand('dmytrof:import:items', ['--all' => true]);

        $tester->assertCommandIsSuccessful();
        $this->assertStringContainsString('There are 1 scheduled items', $tester->getDisplay());
        $this->assertStringContainsString('Done! Imported 1 items', $tester->getDisplay());
        $this->assertSame(['Apple' => 1.5, 'Banana' => 0.25, 'Cherry' => 3.0], $this->products());
        $this->assertSame([], $this->itemManager->getScheduledImportItems());
    }

    public function testImportItemsWithoutScheduledItems(): void
    {
        $tester = $this->executeCommand('dmytrof:import:items');

        $tester->assertCommandIsSuccessful();
        $this->assertStringContainsString('No scheduled items for now', $tester->getDisplay());
    }
}
