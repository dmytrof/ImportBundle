<?php

namespace Dmytrof\ImportBundle\Tests\Functional\App\Manager;

use Dmytrof\ImportBundle\Tests\Functional\App\Entity\Product;
use Dmytrof\ImportBundle\Tests\Functional\App\Form\ProductType;
use Dmytrof\ModelsManagementBundle\Manager\AbstractDoctrineManager;

class ProductManager extends AbstractDoctrineManager
{
    public const MODEL_CLASS = Product::class;
    public const FORM_TYPE_CREATE_ITEM = ProductType::class;
}
