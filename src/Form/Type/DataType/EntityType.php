<?php

/*
 * This file is part of the DmytrofImportBundle package.
 *
 * (c) Dmytro Feshchenko <dmytro.feshchenko@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dmytrof\ImportBundle\Form\Type\DataType;

use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Component\Form\{AbstractType, Extension\Core\Type\TextType, FormBuilderInterface};
use Symfony\Component\OptionsResolver\OptionsResolver;
use Dmytrof\ImportBundle\Form\DataTransformer\EntityToPropertyValueTransformer;

class EntityType extends AbstractType
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * EntityType constructor.
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'required' => false,
            'entity_class' => null,
            'entity_property' => 'title',
            'entity_id_property' => 'id',
            'create_entity_if_not_exists' => false,
            'null_on_exception' => false,
            'compound' => false,
            'multiple' => false,
            'multiple_data_type' => EntityToPropertyValueTransformer::MULTIPLE_DATA_TYPE_COLLECTION,
            'query_builder_decorator' => null,
        ]);

        $resolver->setRequired('entity_class');
        $resolver->addAllowedTypes('multiple', ['bool']);
        $resolver->addAllowedTypes('null_on_exception', ['bool']);
        $resolver->addAllowedValues('multiple_data_type', [EntityToPropertyValueTransformer::MULTIPLE_DATA_TYPE_ARRAY, EntityToPropertyValueTransformer::MULTIPLE_DATA_TYPE_COLLECTION]);
        $resolver->addAllowedTypes('query_builder_decorator', ['null', \Closure::class]);
    }

    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->resetModelTransformers()
            ->resetViewTransformers()
        ;
        $builder->addViewTransformer((new EntityToPropertyValueTransformer($this->registry, $options['entity_class'], $options['entity_property']))
            ->setEntityIdProperty($options['entity_id_property'])
            ->setMultiple($options['multiple'])
            ->setMultipleDataType($options['multiple_data_type'])
            ->setAllowNull(!$options['required'])
            ->setNullOnException($options['null_on_exception'])
            ->setCreateEntityIfNotExists($options['create_entity_if_not_exists'])
            ->setQueryBuilderDecorator($options['query_builder_decorator'])
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return TextType::class;
    }
}