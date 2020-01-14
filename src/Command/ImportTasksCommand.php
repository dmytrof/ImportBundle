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
use Dmytrof\ImportBundle\Manager\TaskManager;

class ImportTasksCommand extends Command
{
    use LockableTrait;

    /**
     * @var TaskManager
     */
    private $taskManager;

    /**
     * ImportTasksCommand constructor.
     * @param TaskManager $taskManager
     * @param string|null $name
     */
    public function __construct(TaskManager $taskManager, string $name = null)
    {
        parent::__construct($name);
        $this->taskManager = $taskManager;
    }

    /**
	 * ImportCommand configuration.
	 */
	protected function configure()
	{
		$this
			->setName('dmytrof:import:tasks')
			->setDescription('Doing import of tasks.')
			->setHelp('Allows to import data records from resources.')

			->addOption('task', 't', InputOption::VALUE_OPTIONAL, 'Import task id.', null)
            ->addOption('scheduled', 's', InputOption::VALUE_OPTIONAL, 'All scheduled tasks', true)
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force import')
		;
	}

    /**
     * Returns task manager
     * @return TaskManager
     */
	protected function getTaskManager(): TaskManager
    {
        return $this->taskManager;
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
        if (!$this->lock()) {
            $output->writeln('The command is already running in another process.');
            return 0;
        }
        if ($input->getOption('task')) {
            $this->importTask($input->getOption('task'), $output, $input);
        } else {
            $taskIds = [];
            foreach ($this->getTaskManager()->getScheduledImportTasks() as $task) {
                array_push($taskIds, $task->getId());
            }
            $tasksCount = sizeof($taskIds);
            $io = new SymfonyStyle($input, $output);
            if ($tasksCount) {
                $io->note(sprintf('There are %s scheduled tasks', $tasksCount));
                foreach ($taskIds as $taskId) {
                    $this->importTask($taskId, $output, $input);
                }
            } else {
                $io->note('No scheduled tasks for now');
            }
        }
        $this->release();
	}

    /**
     * @param int $taskId
     * @param OutputInterface $output
     * @param InputInterface $input
     */
    protected function importTask(int $taskId, OutputInterface $output, InputInterface $input)
    {
        try {
            $this->getTaskManager()->importTask($taskId, $input->getOption('force'), $output, $input);
        } catch (\Exception $e) {
            $io = new SymfonyStyle($input, $output);
            $io->error(sprintf('Import task "%s" error: %s', $taskId, $e->getMessage()));
        }
    }
}