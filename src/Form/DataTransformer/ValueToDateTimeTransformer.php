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

use Symfony\Component\Form\DataTransformerInterface;

class ValueToDateTimeTransformer implements DataTransformerInterface
{
    /**
     * {@inheritdoc}
     */
    public function transform($date)
    {
        if ($date instanceof \DateTimeInterface) {
            return $date->format(\DateTimeInterface::ATOM);
        }
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($dateStr)
    {
        if ($dateStr instanceof \DateTimeInterface) {
            $dateStr->setTimezone((new \DateTime())->getTimezone());
            return $dateStr;
        }
        try {
            $date = new \DateTime((string) $dateStr);
            $date->setTimezone((new \DateTime())->getTimezone());
            return $date;
        } catch (\Exception $e) {
            return null;
        }
    }
}