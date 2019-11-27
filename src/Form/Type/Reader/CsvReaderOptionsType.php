<?php

/*
 * This file is part of the DmytrofImportBundle package.
 *
 * (c) Dmytro Feshchenko <dmytro.feshchenko@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dmytrof\ImportBundle\Form\Type\Reader;

use Symfony\Component\{Form\AbstractType,
    Form\Extension\Core\Type\CheckboxType,
    Form\Extension\Core\Type\TextType,
    Form\FormBuilderInterface,
    OptionsResolver\OptionsResolver};
use Dmytrof\ImportBundle\{Reader\Options\CsvReaderOptions};

class CsvReaderOptionsType extends AbstractType
{
    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => CsvReaderOptions::class,
            'label' => false,
            'api_choices' => false,
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('hasHeadingRow', CheckboxType::class, [
                'label' => 'label.import_task.reader_options.csv.has_heading_row',
                'required' => false,
            ])
            ->add('skipEmptyRows', CheckboxType::class, [
                'label' => 'label.import_task.reader_options.csv.skip_empty_rows',
                'required' => false,
            ])
            ->add('delimiter', TextType::class, [
                'label' => 'label.import_task.reader_options.csv.delimiter',
                'required' => false,
            ])
            ->add('enclosure', TextType::class, [
                'label' => 'label.import_task.reader_options.csv.enclosure',
                'required' => false,
            ])
            ->add('escape', TextType::class, [
                'label' => 'label.import_task.reader_options.csv.escape',
                'required' => false,
            ])
        ;
    }

    /**
     * {@inheritDoc}
     */
    public function getParent()
    {
        return AbstractReaderOptionsType::class;
    }
}