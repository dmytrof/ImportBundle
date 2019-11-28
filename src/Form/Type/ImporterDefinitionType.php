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

use Symfony\Component\Form\{AbstractType, FormBuilderInterface};
use Symfony\Component\OptionsResolver\OptionsResolver;
use Dmytrof\ImportBundle\{Importer\ImporterInterface, Model\ImporterDefinition, Service\ImportersContainer};

class ImporterDefinitionType extends AbstractType
{
    /**
     * @var ImportersContainer
     */
    protected $importersContainer;

    /**
     * ImporterDefinitionType constructor.
     * @param ImportersContainer $importersContainer
     */
    public function __construct(ImportersContainer $importersContainer)
    {
        $this->importersContainer = $importersContainer;
    }

    /**
     * @return ImportersContainer
     */
    public function getImportersContainer(): ImportersContainer
    {
        return $this->importersContainer;
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ImporterDefinition::class,
            'label' => 'label.import_task.importer_definition',
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('code', ImporterType::class, [
                'label' => 'label.import_task.importer',
            ])
        ;
        /** @var ImporterInterface $importer */
        foreach ($this->getImportersContainer() as $importer) {
            if ($importer->hasOptions()) {
                $builder->add($importer->getCode(), $importer->getOptionsFormClass(), [
                    'label'       => 'label.import_task.importer_options.label',
                    'path_delimiter' => call_user_func([$importer->getOptionsClass(), 'getPathDelimiter']),
                    'id_fields_delimiter' => call_user_func([$importer->getOptionsClass(), 'getIdFieldsDelimiter']),
                    'importable_fields' => $importer->getImportableFields(),
                ]);
            }
        }
    }

    public function getBlockPrefix()
    {
        return 'dmytrof_import_task_importer_definition';
    }
}