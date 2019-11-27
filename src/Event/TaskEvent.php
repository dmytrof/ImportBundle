<?php

/*
 * This file is part of the DmytrofImportBundle package.
 *
 * (c) Dmytro Feshchenko <dmytro.feshchenko@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dmytrof\ImportBundle\Event;

use Dmytrof\ImportBundle\Model\Task;
use Symfony\Component\EventDispatcher\Event;

class TaskEvent extends Event
{
    public const PRE_IMPORT_DATA    = 'dmytrof.import.task.pre_import_data';
    public const POST_IMPORT_DATA   = 'dmytrof.import.task.post_import_data';

    /**
     * @var Task
     */
    protected $task;

    /**
     * TaskEvent constructor.
     * @param Task $task
     */
    public function __construct(Task $task)
    {
        $this->task = $task;
    }

    /**
     * Returns task
     * @return Task
     */
    public function getTask(): Task
    {
        return $this->task;
    }
}