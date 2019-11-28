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
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Dmytrof\ImportBundle\Service\ImportersContainer;
use Dmytrof\ImportBundle\Form\DataTransformer\ValueToStringTransformer;

class ImporterType extends AbstractType
{
    /**
     * @var ImportersContainer
     */
    protected $importersContainer;

    /**
     * ImporterType constructor.
     * @param ImportersContainer $importersContainer
     */
    public function __construct(ImportersContainer $importersContainer)
    {
        $this->importersContainer = $importersContainer;
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'choices' => array_flip($this->importersContainer->getImportersTitles()),
            'label' => 'label.importer_task.importer',
            'multiple' => false,
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addModelTransformer(new ValueToStringTransformer($options));
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return ChoiceType::class;
    }
}