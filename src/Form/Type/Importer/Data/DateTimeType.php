<?php

/*
 * This file is part of the DmytrofImportBundle package.
 *
 * (c) Dmytro Feshchenko <dmytro.feshchenko@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dmytrof\ImportBundle\Form\Type\Importer\Data;

use Dmytrof\ImportBundle\Form\DataTransformer\ValueToDateTimeTransformer;
use Symfony\Component\Form\{AbstractType, Extension\Core\Type\TextType, FormBuilderInterface};

class DateTimeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->resetViewTransformers();
        $builder->addViewTransformer(new ValueToDateTimeTransformer());
    }

    /**
     * @return null|string
     */
    public function getParent()
    {
        return TextType::class;
    }
}