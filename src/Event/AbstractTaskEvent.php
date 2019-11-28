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
use Symfony\Contracts\EventDispatcher\Event;

abstract class AbstractTaskEvent extends Event
{
    /**
     * @var Task
     */
    protected $task;

    /**
     * AbstractTaskEvent constructor.
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