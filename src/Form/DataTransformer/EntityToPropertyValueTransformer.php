<?php

/*
 * This file is part of the DmytrofImportBundle package.
 *
 * (c) Dmytro Feshchenko <dmytro.feshchenko@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dmytrof\ImportBundle\Form\DataTransformer;

use Dmytrof\ModelsManagementBundle\Repository\EntityRepositoryInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Inflector\Inflector;
use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Component\Form\{DataTransformerInterface, Exception\TransformationFailedException};

class EntityToPropertyValueTransformer implements DataTransformerInterface
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var string
     */
    protected $entityClass;

    /**
     * @var string
     */
    protected $entityProperty;

    /**
     * @var string
     */
    protected $entityPropertyCondition;

    /**
     * @var bool
     */
    protected $allowNull;

    /**
     * EntityToPropertyValueTransformer constructor.
     * @param ManagerRegistry $registry
     * @param string $entityClass
     * @param string $entityProperty
     * @param string $entityPropertyCondition
     * @param bool $allowNull
     */
    public function __construct(ManagerRegistry $registry, string $entityClass, string $entityProperty, string $entityPropertyCondition, bool $allowNull = true)
    {
        $this->registry = $registry;
        $this->entityClass = $entityClass;
        $this->entityProperty = $entityProperty;
        $this->entityPropertyCondition = $entityPropertyCondition;
        $this->allowNull = $allowNull;
    }

    /**
     * Returns repository
     * @return EntityRepository
     */
    protected function getRepository(): EntityRepository
    {
        return $this->registry->getRepository($this->entityClass);
    }

    /**
     * {@inheritdoc}
     */
    public function transform($entity)
    {
        $value = null;

        if ($entity instanceof $this->entityClass) {
            $value = $entity->{'get'.Inflector::classify($this->entityProperty)}();
        }
        return $value;
    }

    /**
     * Converts value to string
     * @param $value
     * @return null|string
     */
    protected function stringifyValue($value): ?string
    {
        if (is_null($value)) {
            return $value;
        }
        if (is_array($value)) {
            if (sizeof ($value)) {
                return $this->stringifyValue(array_shift($value));
            }
            return null;
        }
        return (string) $value;
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value)
    {
        $value = $this->stringifyValue($value);

        if ($this->allowNull && empty($value)) {
            return null;
        } else if (!$this->allowNull && empty($value)) {
            throw new TransformationFailedException(sprintf('Empty value is not allowed'));
        }

        $repo = $this->getRepository();
//        if ($repo instanceof EntityRepositoryInterface && $repo->getPreConfiguredFilters()->hasFilter($this->filterName)) {
//            $alias = $repo->getAlias();
//            $builder = $repo->getQueryBuilder(['alias' => $alias]);
//            $filter = $repo
//                ->getPreConfiguredFilters()
//                ->getFilter($this->filterName)
//                ->setup($builder, [
//                    'condition' => $this->filterCondition ,
//                    'value' => $value,
//                ], $alias)
//            ;
//            $builder->andWhere($filter->buildWhereCondition($builder));
//            $result = $builder->getQuery()->getResult();
//            $entity = null;
//            if (!is_array($result)) {
//                $entity = $result;
//            } else if (count($result) > 0) {
//                $entity = array_shift($result);
//            }
//            if (!$entity) {
//                $entity = $this->createNewEntity($value);
//            }
//            if (!$entity instanceof $this->modelClass) {
//                throw new TransformationFailedException(sprintf('The entity with %s "%s" could not be found', $this->modelProperty, $value));
//            }
//            return $entity;
//
//        } else {
            throw new TransformationFailedException(sprintf('Unable to find the entity from repository %s', get_class($repo)));
//        }
    }

    /**
     * Creates new entity
     * @param string $value
     * @return mixed
     */
    protected function createNewEntity(string $value)
    {
        if ($this->getRepository() instanceof EntityRepositoryInterface) {
            $entity = $this->getRepository()->createNew();
            $entity->{'set'.Inflector::classify($this->entityProperty)}($value);
            $this->registry->getManager()->persist($entity);
            $this->registry->getManager()->flush();
            return $entity;
        }
        throw new TransformationFailedException(sprintf('Unable to create new entity from repository %s', get_class($this->getRepository())));
    }
}
