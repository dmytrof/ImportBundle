<?php

/*
 * This file is part of the DmytrofImportBundle package.
 *
 * (c) Dmytro Feshchenko <dmytro.feshchenko@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dmytrof\ImportBundle\Form\Type\Importer;

use Symfony\Component\Form\{AbstractType, Extension\Core\Type\TextType, FormInterface, FormView};
use Symfony\Component\OptionsResolver\OptionsResolver;
use Dmytrof\ImportBundle\Importer\Options\ImporterOptions;

class PathToDataType extends AbstractType
{
    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'path_delimiter' => ImporterOptions::getPathDelimiter(),
        ]);
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['pathDelimiter'] = $options['path_delimiter'];
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return TextType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'dmytrof_import_task_importer_options_data_path';
    }
}