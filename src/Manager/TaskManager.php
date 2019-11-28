<?php

/*
 * This file is part of the DmytrofImportBundle package.
 *
 * (c) Dmytro Feshchenko <dmytro.feshchenko@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dmytrof\ImportBundle\Manager;

use Doctrine\Common\Persistence\ManagerRegistry;
use Dmytrof\ModelsManagementBundle\Manager\AbstractDoctrineManager;
use Dmytrof\ImportBundle\{
    Exception\TaskNotFoundException as NotFoundException,
    Form\Type\Api\TaskType as CreateType,
    Model\Task,
    Entity\Task\Task as Entity
};
use Dmytrof\ImportBundle\Service\Logger;
use Symfony\Component\Console\{
    Input\InputInterface, Output\OutputInterface, Style\SymfonyStyle
};
use Symfony\Component\{
    Form\FormFactoryInterface, Validator\Validator\ValidatorInterface
};

class TaskManager extends AbstractDoctrineManager
{
    const MODEL_CLASS = Entity::class;
    const EXCEPTION_CLASS_NOT_FOUND = NotFoundException::class;
    const FORM_TYPE_CREATE_ITEM = CreateType::class;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * TaskManager constructor.
     * @param ManagerRegistry $registry
     * @param ValidatorInterface $validator
     * @param FormFactoryInterface $formFactory
     * @param Logger $logger
     */
    public function __construct(ManagerRegistry $registry, ValidatorInterface $validator, FormFactoryInterface $formFactory, Logger $logger)
    {
        $this->logger = $logger;
        parent::__construct($registry, $validator, $formFactory);
    }

    /**
     * @return Logger
     */
    public function getLogger(): Logger
    {
        return $this->logger;
    }

    /**
     * Returns task
     * @param int|null $id
     * @return Task
     */
    public function getTask(?int $id): Task
    {
        return $this->getItem($id);
    }

    /**
     * Returns available task
     * @param int|null $id
     * @return Task
     */
    public function getAvailableItem(?int $id): Task
    {
        return $this->getTask($id);
    }

    /**
     * Imports task data
     * @param int $taskId
     * @param OutputInterface|null $output
     * @param InputInterface|null $input
     */
    public function importTask(int $taskId, OutputInterface $output = null, InputInterface $input = null): void
    {
        $task = $this->getTask($taskId);
        $io = new SymfonyStyle($input, $output);
        $task->importData($this, $io, $this->getLogger());
    }

    /**
     * Returns scheduled import tasks
     * @return Task[]
     */
    public function getScheduledImportTasks(): array
    {
        return $this->getRepository()->getScheduledImportTasks();
    }
}