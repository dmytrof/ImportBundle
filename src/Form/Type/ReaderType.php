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
    /**
     * @var ReadersContainer
     */
    protected $readersContainer;

    /**
     * ReaderType constructor.
     * @param ReadersContainer $readersContainer
     */
    public function __construct(ReadersContainer $readersContainer)
    {
        $this->readersContainer = $readersContainer;
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver)
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