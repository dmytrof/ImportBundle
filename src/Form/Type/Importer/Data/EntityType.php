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
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'required' => false,
            'entity_class' => null,
            'entity_property' => 'title',
            'compound' => false,
        ]);

        $resolver->setRequired('entity_class');
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
        $builder->addViewTransformer(new EntityToPropertyValueTransformer($this->registry, $options['entity_class'], $options['entity_property'], !$options['required']));
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return TextType::class;
    }
}