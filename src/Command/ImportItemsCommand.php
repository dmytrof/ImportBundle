<?php

/*
 * This file is part of the DmytrofImportBundle package.
 *
 * (c) Dmytro Feshchenko <dmytro.feshchenko@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dmytrof\ImportBundle\Command;

use Symfony\Component\Console\Command\{Command, LockableTrait};
use Symfony\Component\Console\{Style\SymfonyStyle, Output\OutputInterface};
use Symfony\Component\Console\Input\{InputInterface, InputOption};
use Dmytrof\ImportBundle\Manager\ItemManager;

class ImportItemsCommand extends Command
{
    use LockableTrait;

    /**
     * @var ItemManager
     */
    private $itemManager;

    /**
     * ImportItemsCommand constructor.
     * @param ItemManager $itemManager
     * @param string|null $name
     */
    public function __construct(ItemManager $itemManager, string $name = null)
    {
        parent::__construct($name);
        $this->itemManager = $itemManager;
    }

    /**
	 * ImportCommand configuration.
	 */
	protected function configure()
	{
		$this
			->setName('dmytrof:import:items')
			->setDescription('Doing import of scheduled items.')
			->setHelp('Allows to import data records from resources.')

			->addOption('item', 'i', InputOption::VALUE_OPTIONAL, 'Import item id.')
            ->addOption('all', 'a', InputOption::VALUE_OPTIONAL, 'All scheduled items')
            ->addOption('batch', 'b', InputOption::VALUE_OPTIONAL, 'Scheduled batch', 1000)
            ->addOption('task', 't', InputOption::VALUE_OPTIONAL, 'Import task id')
            ->addOption('period', 'p', InputOption::VALUE_OPTIONAL, 'Period in seconds to execute commend')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force import')
            ->addOption('throw-exceptions', null, InputOption::VALUE_NONE, 'Throw exceptions')
		;
	}

    /**
     * Returns item manager
     * @return ItemManager
     */
	protected function getItemManager(): ItemManager
    {
        return $this-$this->itemManager;
    }

	/**
	 * ImportCommand logic.
	 *
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return int|null|void
	 * @throws \Exception
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
        $io = new SymfonyStyle($input, $output);
        if (!$this->lock()) {
            $io->warning('The command is already running in another process.');
            return 0;
        }
        $startTime = microtime(true);
        $i=0;
        if ($input->getOption('item')) {
            $this->importItem($input->getOption('item'), $output, $input);
            $i++;
        } else {
            $itemsCount = 0;
            $break = false;
            $checkBreak = function () {return false;};
            if ($input->getOption('period')) {
                $period = ((int) $input->getOption('period')) * 0.99; //seconds
                $checkBreak = function () use ($period, $startTime, $input, $io) {
                    if ((microtime(true) - $startTime) >= $period) {
                        $io->note(sprintf('Period time\'s up (%s seconds)!', $input->getOption('period')));
                        return true;
                    }
                    return false;
                };
            }

            do {
                $itemIds = $this->getItemManager()->getScheduledImportItems($input->getOption('batch'), $input->getOption('task'));
                $itemsCount += $count = sizeof($itemIds);
                if ($count) {
                    if (!$i) {
                        $io->note(sprintf('There are %s scheduled items', $itemsCount));
                    }
                    foreach ($itemIds as $itemId) {
                        $break = $checkBreak->call($this);
                        if ($break) {
                            break;
                        }
                        $io->text(sprintf('Importing %s/%s scheduled items. Memory usage: %01.2f Mb. Execution time: %01.1f sec', ++$i, $itemsCount, memory_get_usage()/1000000, (microtime(true) - $startTime)));
                        $this->importItem($itemId, $output, $input);
                    }
                } else {
                    if (!$i) {
                        $io->note('No scheduled items for now');
                    }
                }
            } while ($input->hasOption('all') && $count && !$break);
        }
        $io->note(sprintf('Done! Imported %s items. Memory usage: %01.2f Mb. Execution time: %01.1f sec', $i, memory_get_usage()/1000000, (microtime(true) - $startTime)));
        $this->release();
	}

    /**
     * @param string $itemId
     * @param OutputInterface $output
     * @param InputInterface $input
     */
    protected function importItem(string $itemId, OutputInterface $output, InputInterface $input)
    {
        try {
            $this->getItemManager()->importItem($itemId, $output, $input);
        } catch (\Exception $e) {
            $io = new SymfonyStyle($input, $output);
            $io->error('Import item '.$itemId.' error: '.$e->getMessage());
            if ($input->getOption('throw-exceptions')) {
                throw $e;
            }
        }
    }
}