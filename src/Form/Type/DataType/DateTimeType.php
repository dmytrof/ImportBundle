<?php

/*
 * This file is part of the DmytrofImportBundle package.
 *
 * (c) Dmytro Feshchenko <dmytro.feshchenko@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dmytrof\ImportBundle\Form\Type\DataType;

use Dmytrof\ImportBundle\Form\DataTransformer\ValueToDateTimeTransformer;
use Symfony\Component\Form\{AbstractType, FormBuilderInterface};
use Symfony\Component\Form\Extension\Core\Type\TextType;

class DateTimeType extends AbstractType
{
    /**
     * @inheritDoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->resetViewTransformers();
        $builder->addViewTransformer(new ValueToDateTimeTransformer());
    }

    /**
     * @inheritDoc
     */
    public function getParent(): ?string
    {
        return TextType::class;
    }
}