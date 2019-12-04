<?php

/*
 * This file is part of the DmytrofImportBundle package.
 *
 * (c) Dmytro Feshchenko <dmytro.feshchenko@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dmytrof\ImportBundle\Entity\Item;

use Doctrine\ORM\{EntityRepository, NonUniqueResultException, QueryBuilder};
use Dmytrof\ModelsManagementBundle\Repository\{EntityRepositoryInterface, Traits\EntityRepositoryTrait};
use Dmytrof\ImportBundle\Model\Item as ItemModel;

class Repository extends EntityRepository implements EntityRepositoryInterface
{
    use EntityRepositoryTrait;

    protected $alias = 'iti';

    /**
     * Returns imported item
     * @param int $taskId
     * @param string $entryId
     * @param string|null $id
     * @return Item|null
     */
    public function getImportedItem(int $taskId, string $entryId, ?string $id = null): ?Item
    {
        $builder = $this->getQueryBuilder();
        $builder
            ->andWhere($this->getAlias().'.taskId = :taskId')
            ->setParameter('taskId', $taskId)
            ->andWhere($this->getAlias().'.entryId = :entryId')
            ->setParameter('entryId', $entryId)
        ;
        try {
            return $builder->getQuery()->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            if ($id) {
                return $this->find($id);
            }
            throw $e;
        }
    }
    /**
     * Returns scheduled import tasks
     * @param int $batch
     * @param int|null $taskId
     * @return array
     */
    public function getScheduledImportItems(int $batch = 100, ?int $taskId = null): array
    {
        $alias = $this->getAlias();
        $builder = $this->getQueryBuilder();
        $builder
            ->select($alias.'.id')
            ->andWhere($alias.'.statusId = :statusId')
            ->setParameter('statusId', ItemModel::STATUS_SCHEDULED)
            ->setMaxResults($batch)
        ;
        if ($taskId) {
            $builder
                ->andWhere($alias.'.taskId = :taskId')
                ->setParameter('taskId', $taskId)
            ;
        }
        $result = $builder->getQuery()->getScalarResult();

        return array_column($result, 'id');
    }
}