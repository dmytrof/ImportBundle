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
use Dmytrof\ImportBundle\Form\DataTransformer\ValueToStringTransformer;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Dmytrof\ImportBundle\Service\ReadersContainer;

class ReaderType extends AbstractType
{
    public function __construct(
        protected ReadersContainer $readersContainer,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'choices' => array_flip($this->readersContainer->getReadersTitles()),
            'label' => 'label.reader_task.reader',
            'multiple' => false,
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer(new ValueToStringTransformer($options));
    }

    /**
     * {@inheritdoc}
     */
    public function getParent(): ?string
    {
        return ChoiceType::class;
    }
}
