<?php

/*
 * This file is part of the DmytrofImportBundle package.
 *
 * (c) Dmytro Feshchenko <dmytro.feshchenko@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dmytrof\ImportBundle\Form\Type\Api;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\{AbstractType,
    Extension\Core\Type\IntegerType,
    Extension\Core\Type\TextareaType,
    FormBuilderInterface,
    Extension\Core\Type\TextType};

use Dmytrof\ImportBundle\{Form\Type\ImporterDefinitionType,
    Form\Type\ReaderDefinitionType,
    Manager\TaskManager as Manager};

class TaskType extends AbstractType
{
    /**
     * @var Manager
     */
    protected $manager;

    /**
     * TaskType constructor.
     * @param Manager $manager
     */
    public function __construct(Manager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * @return Manager
     */
    public function getManager(): Manager
    {
        return $this->manager;
    }

    /**
	 * {@inheritDoc}
	 */
	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults([
			'data_class' => $this->getManager()->getModelClass(),
			'csrf_protection' => false,
		]);
	}

	/**
	 * {@inheritDoc}
	 */
	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		$builder
            ->add('title', TextType::class, [
				'required' => true,
                'label' => 'label.import_task.title',
			])
            ->add('link', TextareaType::class, [
                'required' => true,
                'label' => 'label.import_task.link',
            ])
            ->add('readerDefinition', ReaderDefinitionType::class, [
                'label' => 'label.import_task.reader_definition.label',
                'required' => true,
                'api_choices' => true,
            ])
            ->add('importerDefinition', ImporterDefinitionType::class, [
                'label' => 'label.import_task.importer_definition.label',
                'required' => true,
                'api_choices' => true,
            ])
            ->add('period', IntegerType::class, [
                'label' => 'label.import_task.period',
                'required' => false,
            ])
		;
	}
}