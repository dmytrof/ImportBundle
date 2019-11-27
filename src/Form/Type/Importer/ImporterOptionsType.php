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

use Dmytrof\ImportBundle\Model\ImportableFields;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\{AbstractType, FormBuilderInterface};
use Dmytrof\ImportBundle\Importer\Options\ImporterOptions;

class ImporterOptionsType extends AbstractType
{
    /**
	 * {@inheritDoc}
	 */
	public function configureOptions(OptionsResolver $resolver)
	{
        $resolver->setRequired('importable_fields');
        $resolver->setAllowedTypes('importable_fields', [ImportableFields::class]);

		$resolver->setDefaults([
			'csrf_protection' => false,
            'api_choices' => false,
            'data_class' => ImporterOptions::class,
            'path_delimiter' => ImporterOptions::getPathDelimiter(),
            'id_fields_delimiter' => ImporterOptions::getIdFieldsDelimiter(),
		]);
	}

    /**
     * {@inheritDoc}
     */
	public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('dataPath', PathToDataType::class, [
                'label' => 'label.import_task.importer_options.data_path.label',
                'required' => false,
                'path_delimiter' => $options['path_delimiter'],
            ])
            ->add('itemHashIdFieldsStr', HashIdFieldsType::class, [
                'label' => 'label.import_task.importer_options.item_hash_id_fields.label',
                'required' => true,
                'id_fields_delimiter' => $options['id_fields_delimiter'],
            ])
            ->add('importableFieldsOptions', ImportableFieldsOptionsType::class, [
                'label' => 'label.import_task.importer_options.importable_fields.label',
                'required' => true,
                'path_delimiter' => $options['path_delimiter'],
                'importable_fields' => $options['importable_fields'],
            ])
        ;
    }
}