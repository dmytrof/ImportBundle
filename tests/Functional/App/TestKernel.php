<?php

namespace Dmytrof\ImportBundle\Tests\Functional\App;

use Dmytrof\ImportBundle\DmytrofImportBundle;
use Dmytrof\ModelsManagementBundle\DmytrofModelsManagementBundle;
use Composer\InstalledVersions;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\MonologBundle\MonologBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

class TestKernel extends Kernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        return [
            new FrameworkBundle(),
            new DoctrineBundle(),
            new MonologBundle(),
            new DmytrofModelsManagementBundle(),
            new DmytrofImportBundle(),
        ];
    }

    public function getProjectDir(): string
    {
        return __DIR__;
    }

    public function getCacheDir(): string
    {
        return sys_get_temp_dir().'/dmytrof_import_bundle_tests/cache/'.$this->environment;
    }

    public function getLogDir(): string
    {
        return sys_get_temp_dir().'/dmytrof_import_bundle_tests/logs';
    }

    protected function configureContainer(ContainerBuilder $container, LoaderInterface $loader): void
    {
        $loader->load(__DIR__.'/config/config.yaml');

        // DoctrineBundle 2.x needs PHP 8.4 native lazy objects to be enabled explicitly (DoctrineBundle 3 always uses them)
        if (version_compare((string) InstalledVersions::getVersion('doctrine/doctrine-bundle'), '3.0.0', '<')) {
            $container->loadFromExtension('doctrine', ['orm' => ['enable_native_lazy_objects' => true]]);
        }
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        // Route used by ReaderDefinitionType
        $routes->add('api_import_data_v1_post_parse_link', '/import/parse-link');
    }
}
