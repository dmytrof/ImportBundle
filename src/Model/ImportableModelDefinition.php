<?php

/*
 * This file is part of the DmytrofImportBundle package.
 *
 * (c) Dmytro Feshchenko <dmytro.feshchenko@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dmytrof\ImportBundle\Model;

use Dmytrof\ImportBundle\Exception\RuntimeException;

class ImportableModelDefinition extends ModelDefinition
{
    /**
     * ImportableModelDefinition constructor.
     * @param \ReflectionClass $reflection
     */
    public function __construct(\ReflectionClass $reflection)
    {
        if (!$reflection->implementsInterface(ImportableModelInterface::class)) {
            throw new RuntimeException(sprintf('Class %s is not instance of %s', $reflection->getName(), ImportableModelInterface::class));
        }
        parent::__construct($reflection);
    }
}