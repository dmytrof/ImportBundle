<?php

/*
 * This file is part of the DmytrofImportBundle package.
 *
 * (c) Dmytro Feshchenko <dmytro.feshchenko@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dmytrof\ImportBundle\Service;

use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger as BaseLogger;

class Logger extends BaseLogger
{
    /**
     * Logger constructor.
     * @param string $name
     * @param string $logsDir
     * @param string $env
     */
    public function __construct(string $name, string $logsDir, string $env = 'dev')
    {
        parent::__construct($name);

        $filename = join('/', [$logsDir, $name, $env]).'.log';
        $handler = new RotatingFileHandler($filename, 20, Logger::INFO, true, 0664);
        $this
            ->pushHandler($handler)
            ->useMicrosecondTimestamps(false)
        ;
    }
}