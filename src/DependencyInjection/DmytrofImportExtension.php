<?php

/*
 * This file is part of the DmytrofImportBundle package.
 *
 * (c) Dmytro Feshchenko <dmytro.feshchenko@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dmytrof\ImportBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\{ContainerBuilder, Loader};
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Dmytrof\ImportBundle\{Importer\ImporterInterface, Reader\ReaderInterface};

class DmytrofImportExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $config = $this->processConfiguration(new Configuration(), $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));

        $loader->load('services.yaml');

        foreach ($config as $key => $value) {
            $container->setParameter($this->getAlias().'.'.$key, $value);
        }

        $container->registerForAutoconfiguration(ImporterInterface::class)
            ->addTag('dmytrof.import.importer');

        $container->registerForAutoconfiguration(ReaderInterface::class)
            ->addTag('dmytrof.import.reader');
    }
}
