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

use Symfony\Component\Form\{AbstractType, FormBuilderInterface, FormInterface, FormView};
use Symfony\Component\{OptionsResolver\OptionsResolver, Routing\RouterInterface};
use Dmytrof\ImportBundle\{Model\ReaderDefinition, Reader\ReaderInterface, Service\ReadersContainer};

class ReaderDefinitionType extends AbstractType
{
    /**
     * @var ReadersContainer
     */
    protected $readersContainer;

    /**
     * @var RouterInterface
     */
    protected $router;

    /**
     * ReaderDefinitionType constructor.
     * @param ReadersContainer $readersContainer
     * @param RouterInterface $router
     */
    public function __construct(ReadersContainer $readersContainer, RouterInterface $router)
    {
        $this->readersContainer = $readersContainer;
        $this->router = $router;
    }

    /**
     * @return ReadersContainer
     */
    public function getReadersContainer(): ReadersContainer
    {
        return $this->readersContainer;
    }

    /**
     * @return RouterInterface
     */
    public function getRouter(): RouterInterface
    {
        return $this->router;
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ReaderDefinition::class,
            'label' => 'label.import_task.reader_definition',
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('code', ReaderType::class, [
                'label' => 'label.import_task.reader',
                'api_choices' => $options['api_choices'],
            ])
        ;

        /** @var ReaderInterface $reader */
        foreach ($this->getReadersContainer() as $reader) {
            if ($reader->hasOptions()) {
                $builder->add($reader->getCode(), $reader->getOptionsFormClass(), [
                    'label' => 'label.import_task.reader_options.label',
                ]);
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['parseLinkUri'] = $this->getRouter()->generate('api_import_data_v1_post_parse_link');
    }

    /**
     * {@inheritDoc}
     */
    public function getBlockPrefix()
    {
        return 'dmytrof_import_task_reader_definition';
    }
}