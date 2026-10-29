<?php

namespace Dmytrof\ImportBundle\Tests\Unit\EventListener;

use Dmytrof\ImportBundle\EventListener\ImportDoctrineSubscriber;
use Dmytrof\ImportBundle\Manager\TaskManager;
use Dmytrof\ImportBundle\Model\{Item, Task};
use Dmytrof\ImportBundle\Tests\Fixtures\CreatesTasksTrait;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;

class ImportDoctrineSubscriberTest extends TestCase
{
    use CreatesTasksTrait;

    private function postLoad(object $entity, ImportDoctrineSubscriber $listener): void
    {
        $listener->postLoad(new LifecycleEventArgs($entity, $this->createStub(ObjectManager::class)));
    }

    public function testInjectsServices(): void
    {
        $taskManager = $this->createStub(TaskManager::class);
        $dispatcher = new EventDispatcher();
        $listener = new ImportDoctrineSubscriber($taskManager, $this->createImportersContainer(), $this->createReadersContainer(), $dispatcher);

        $task = new Task();
        $this->postLoad($task, $listener);
        $this->assertSame($this->importersContainer, $task->getImportersContainer());
        $this->assertSame($this->readersContainer, $task->getReadersContainer());
        $this->assertSame($dispatcher, $task->getEventDispatcher());

        $item = new Item();
        $this->postLoad($item, $listener);
        $this->assertSame($taskManager, $item->getTaskManager());

        $this->postLoad(new \stdClass(), $listener); // other entities are ignored
    }
}
