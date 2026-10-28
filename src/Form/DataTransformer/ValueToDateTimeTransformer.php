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
    public function transform(mixed $date): mixed
    {
        if ($date instanceof \DateTimeInterface) {
            return $date->format(\DateTimeInterface::ATOM);
        }
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform(mixed $dateStr): mixed
    {
        if ($dateStr instanceof \DateTimeInterface) {
            // setTimezone() returns a new instance for \DateTimeImmutable
            return $dateStr->setTimezone((new \DateTime())->getTimezone());
        }
        try {
            $dateStr = (string) $dateStr;
            if (!$dateStr) {
                return null;
            }
            $date = new \DateTime((string) $dateStr);
            $date->setTimezone((new \DateTime())->getTimezone());
            return $date;
        } catch (\Exception $e) {
            return null;
        }
    }
}