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

use Symfony\Component\Form\{AbstractType, FormBuilderInterface};
use Symfony\Component\OptionsResolver\OptionsResolver;
use Dmytrof\ImportBundle\Importer\Options\ImporterOptions;
use Dmytrof\ImportBundle\Model\{ImportableField, ImportableFields, ImportableFieldsOptions};

class ImportableFieldsOptionsType extends AbstractType
{
    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired('importable_fields');
        $resolver->setAllowedTypes('importable_fields', [ImportableFields::class]);

        $resolver->setDefaults([
            'path_delimiter' => ImporterOptions::getPathDelimiter(),
            'data_class' => ImportableFieldsOptions::class,
        ]);
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var ImportableField $field */
        foreach ($options['importable_fields'] as $field) {
            $builder
                ->add($field->getName(), ImportableFieldOptionsType::class, [
                    'label' => $field->getLabel() ?: 'Undefined',
                    'required' => false,
                ])
            ;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'dmytrof_import_task_importer_options_importable_fields_options';
    }
}