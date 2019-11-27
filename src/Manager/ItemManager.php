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

use Dmytrof\ModelsManagementBundle\Manager\AbstractDoctrineanager;

use Dmytrof\ImportBundle\{Exception\ItemNotFoundException as NotFoundException,
    Model\Item,
    Entity\Item\Item as Entity,
    Model\Task};
use Dmytrof\ImportBundle\Service\LoggerManager;
use Monolog\Logger;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Console\{
    Input\InputInterface, Output\OutputInterface, Style\SymfonyStyle
};
use Symfony\Component\{
    Form\FormFactoryInterface, Validator\Validator\ValidatorInterface
};

class ItemManager extends AbstractDoctrineManager
{
    const MODEL_CLASS = Entity::class;
    const EXCEPTION_CLASS_NOT_FOUND = NotFoundException::class;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * TaskManager constructor.
     * @param RegistryInterface $registry
     * @param ValidatorInterface $validator
     * @param FormFactoryInterface $formFactory
     * @param LoggerManager $loggerManager
     */
    public function __construct(RegistryInterface $registry, ValidatorInterface $validator, FormFactoryInterface $formFactory, LoggerManager $loggerManager)
    {
        $this->logger = $loggerManager->get('import');
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
     * Returns available task
     * @param string|null $id
     * @return Item
     */
    public function getAvailableItem(?string $id): Item
    {
        return $this->getItem($id);
    }

    /**
     * Returns imported item
     * @param int $taskId
     * @param string $entryId
     * @param string|null $id
     * @return Item
     */
    public function getImportedItem(int $taskId, string $entryId, ?string $id = null): Item
    {
        return $this->getRepository()->getImportedItem($taskId, $entryId, $id)
            ?: ($this->new())
                ->setTaskId($taskId)
                ->setEntryId($entryId)
        ;
    }

    /**
     * Returns new imported item
     * @param int $taskId
     * @param string $entryId
     * @return Item
     */
    public function getNewImportedItem(int $taskId, string $entryId): Item
    {
        return ($this->new())
                ->setTaskId($taskId)
                ->setEntryId($entryId)
            ;
    }

    /**
     * Imports item data
     * @param string $itemId
     * @param OutputInterface|null $output
     * @param InputInterface|null $input
     */
    public function importItem(string $itemId, OutputInterface $output = null, InputInterface $input = null): void
    {
        $item = $this->getAvailableItem($itemId);
        $io = new SymfonyStyle($input, $output);
        $item->importScheduledData($this, $io, $this->getLogger());
    }

    /**
     * Returns scheduled import tasks
     * @param int $batch
     * @param int|null $taskId
     * @return array
     */
    public function getScheduledImportItems(int $batch = 100, ?int $taskId = null): array
    {
        return $this->getRepository()->getScheduledImportItems($batch, $taskId);
    }
}