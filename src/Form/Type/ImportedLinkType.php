<?php

/*
 * This file is part of the DmytrofImportBundle package.
 *
 * (c) Dmytro Feshchenko <dmytro.feshchenko@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dmytrof\ImportBundle\Form\Type;

use Symfony\Component\{Form\AbstractType, Form\Extension\Core\Type\TextType, OptionsResolver\OptionsResolver};

class ImportedLinkType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'compound' => false,
        ));
    }

    public function getBlockPrefix()
    {
        return 'dmytrof_import_task_link';
    }

//    /**
//     * {@inheritdoc}
//     */
//    public function getParent()
//    {
//        return TextType::class;
//    }
}