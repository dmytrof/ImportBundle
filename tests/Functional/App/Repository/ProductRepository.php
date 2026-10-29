<?php

namespace Dmytrof\ImportBundle\Tests\Functional\App\Repository;

use Dmytrof\ModelsManagementBundle\Repository\{EntityRepositoryInterface, Traits\EntityRepositoryTrait};
use Doctrine\ORM\EntityRepository;

class ProductRepository extends EntityRepository implements EntityRepositoryInterface
{
    use EntityRepositoryTrait;
}
