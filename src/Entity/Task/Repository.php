<?php

/*
 * This file is part of the DmytrofImportBundle package.
 *
 * (c) Dmytro Feshchenko <dmytro.feshchenko@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dmytrof\ImportBundle\Entity\Task;

use Dmytrof\ModelsManagementBundle\Repository\{EntityRepositoryInterface, Traits\EntityRepositoryTrait};
use Doctrine\ORM\EntityRepository;
use Dmytrof\ImportBundle\Model\Task as TaskModel;

class Repository extends EntityRepository implements EntityRepositoryInterface
{
    use EntityRepositoryTrait;

    public $alias = 'it';

    /**
     * Returns scheduled import tasks
     * @return TaskModel[]
     */
    public function getScheduledImportTasks(): array
    {
        $alias = $this->getAlias();
        $builder = $this->getQueryBuilder();
        $builder
            ->andWhere($alias.'.active = :active')
            ->setParameter('active', true)
            ->andWhere($builder->expr()->isNotNull($alias.'.period'))
            ->andWhere($builder->expr()->orX(
                'DATE_ADD('.$alias.'.importedAt, '.$alias.'.period, \'second\' ) <= :now',
                $builder->expr()->isNull($alias.'.importedAt')
            ))
            ->setParameter('now', new \DateTime())
            ->andWhere($builder->expr()->orX(
                $alias.'.inProgress = :inProgressFalse',
                $alias.'.inProgress = :inProgressTrue AND DATE_ADD('.$alias.'.importedAt, 4, \'hour\' ) <= :now'
            ))
            ->setParameter('inProgressFalse', 0)
            ->setParameter('inProgressTrue', 1)
        ;

        return $builder->getQuery()->getResult();
    }
}