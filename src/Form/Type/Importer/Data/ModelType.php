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

use DFStudio\ModelBundle\Entity\Filter\Filter;
use Dmytrof\ImportBundle\Form\DataTransformer\ModelToPropertyValueTransformer;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Form\{AbstractType, FormBuilderInterface};
use Symfony\Component\OptionsResolver\OptionsResolver;

class ModelType extends AbstractType
{
    /**
     * @var RegistryInterface
     */
    protected $registry;

    /**
     * ModelSelectType constructor.
     * @param RegistryInterface $registry
     */
    public function __construct(RegistryInterface $registry)
    {
        $this->registry = $registry;
    }

    /**
     * Return registry
     * @return RegistryInterface
     */
    protected function getRegistry(): RegistryInterface
    {
        return $this->registry;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'required' => false,
            'model_class' => null,
            'model_property' => 'title',
            'filter_name' => 'title',
            'filter_condition' => Filter::CONDITION_EQUAL,
            'compound' => false,
        ]);

        $resolver->setRequired('model_class');
    }

    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addViewTransformer(new ModelToPropertyValueTransformer($this->getRegistry(), $options['model_class'], $options['model_property'], $options['filter_name'], $options['filter_condition'], !$options['required']));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'string';
    }
}