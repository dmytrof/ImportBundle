<?php

/*
 * This file is part of the DmytrofImportBundle package.
 *
 * (c) Dmytro Feshchenko <dmytro.feshchenko@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dmytrof\ImportBundle\Form\DataTransformer;

class ValueToStringTransformer extends ValueToIntegerTransformer
{
    /**
     * Transforms value
     */
    protected function transformValue(mixed $value): mixed
    {
        return (string) $value;
    }
}