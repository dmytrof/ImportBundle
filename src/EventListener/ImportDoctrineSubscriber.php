<?php

/*
 * This file is part of the DmytrofImportBundle package.
 *
 * (c) Dmytro Feshchenko <dmytro.feshchenko@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dmytrof\ImportBundle\EventListener;

use Doctrine\Persistence\Event\LifecycleEventArgs;
use Dmytrof\ImportBundle\{Manager\TaskManager, Model\Item, Model\Task};
use Dmytrof\ImportBundle\Service\{ImportersContainer,  ReadersContainer};
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Injects services into Task and Item models after they are loaded by Doctrine.
 * Registered as a Doctrine "postLoad" event listener (see Resources/config/services.yaml).
 */
class ImportDoctrineSubscriber
{
    /**
     * @var ImportersContainer
     */
    protected $importersContainer;

    /**
     * @var ReadersContainer
     */
    protected $readersContainer;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var TaskManager
     */
    protected $taskManager;

    public function __construct(
        TaskManager $taskManager,
        ImportersContainer $importersContainer,
        ReadersContainer $readersContainer,
        EventDispatcherInterface $eventDispatcher,
    ) {
        $this->taskManager = $taskManager;
        $this->importersContainer = $importersContainer;
        $this->readersContainer = $readersContainer;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function postLoad(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();
        if ($entity instanceof Task) {
            $entity->setImportersContainer($this->importersContainer);
            $entity->setReadersContainer($this->readersContainer);
            $entity->setEventDispatcher($this->eventDispatcher);
        } else if ($entity instanceof Item) {
            $entity->setTaskManager($this->taskManager);
        }
    }
}
