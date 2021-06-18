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

use Doctrine\ORM\Events;
use Doctrine\Common\EventSubscriber;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Dmytrof\ImportBundle\{Manager\TaskManager, Model\Item, Model\Task};
use Dmytrof\ImportBundle\Service\{ImportersContainer,  ReadersContainer};
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ImportDoctrineSubscriber implements EventSubscriber
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
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var TaskManager
     */
    protected $taskManager;

    /**
     * ImportDoctrineSubscriber constructor.
     * @param TaskManager $taskManager
     * @param ImportersContainer $importersContainer
     * @param ReadersContainer $readersContainer
     * @param EventDispatcherInterface $eventDispatcher
     * @param TranslatorInterface $translator
     */
    public function __construct(TaskManager $taskManager, ImportersContainer $importersContainer, ReadersContainer $readersContainer, EventDispatcherInterface $eventDispatcher, TranslatorInterface $translator)
    {
        $this->taskManager = $taskManager;
        $this->importersContainer = $importersContainer;
        $this->readersContainer = $readersContainer;
        $this->eventDispatcher = $eventDispatcher;
        $this->translator = $translator;
    }

    /**
     * Returns subscribed events
     * @return array
     */
    public function getSubscribedEvents()
    {
        return [
            Events::postLoad,
        ];
    }

    /**
     * @param LifecycleEventArgs $args
     * @return void
     */
    public function postLoad(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();
        if ($entity instanceof Task) {
            $entity->setImportersContainer($this->importersContainer);
            $entity->setReadersContainer($this->readersContainer);
            $entity->setEventDispatcher($this->eventDispatcher);
//            $entity->setTranslator($this->translator);
        } else if ($entity instanceof Item) {
//            $entity->setTranslator($this->translator);
            $entity->setTaskManager($this->taskManager);
        }
    }
}