<?php

namespace Dmytrof\ImportBundle\Tests\Functional\App\Form;

use Dmytrof\ImportBundle\Form\Type\DataType\{FloatType, StringType};
use Dmytrof\ImportBundle\Tests\Functional\App\Entity\Product;
use Symfony\Component\Form\{AbstractType, FormBuilderInterface};
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', StringType::class, ['label' => 'Title'])
            ->add('price', FloatType::class, ['label' => 'Price', 'required' => false])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
            'csrf_protection' => false,
        ]);
    }
}
