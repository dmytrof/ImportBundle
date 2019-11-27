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

use Symfony\Component\Form\{AbstractType,
    CallbackTransformer,
    Extension\Core\Type\TextType,
    FormBuilderInterface,
    FormInterface,
    FormView};
use Symfony\Component\OptionsResolver\OptionsResolver;
use Dmytrof\ImportBundle\Importer\Options\ImporterOptions;
use Dmytrof\ImportBundle\Model\ImportableFieldOptions;

class ImportableFieldOptionsType extends AbstractType
{
    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'path_delimiter' => ImporterOptions::getPathDelimiter(),
            'data_class' => ImportableFieldOptions::class,
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('key', TextType::class, [
                'label' => 'label.import_task.importer_options.importable_fields.key',
                'required' => false,
            ])
            ->add('fallbackKey', TextType::class, [
                'label' => 'label.import_task.importer_options.importable_fields.fallback_key',
                'required' => false,
            ])
            ->add('defaultValue', TextType::class, [
                'label' => 'label.import_task.importer_options.importable_fields.default_value',
                'required' => false,
            ])
        ;
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['pathDelimiter'] = $options['path_delimiter'];
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'dmytrof_import_task_importer_options_importable_field_options';
    }
}