<?php

namespace Dmytrof\ImportBundle\Tests\Functional\App\Importer;

use Dmytrof\ImportBundle\Importer\AbstractImporter;
use Dmytrof\ImportBundle\Tests\Functional\App\Manager\ProductManager;

class ProductImporter extends AbstractImporter
{
    public const CODE = 'products';
    public const TITLE = 'Products';

    public function __construct(ProductManager $manager)
    {
        $this->manager = $manager;
    }
}
