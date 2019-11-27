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

use Dmytrof\ModelsManagementBundle\Entity\EntityRepositoryInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class ModelToPropertyValueTransformer implements DataTransformerInterface
{
    /**
     * @var RegistryInterface
     */
    protected $registry;

    /**
     * @var string
     */
    protected $modelClass;

    /**
     * @var string
     */
    protected $modelProperty;

    /**
     * @var string
     */
    protected $filterName;

    /**
     * @var string
     */
    protected $filterCondition;

    /**
     * @var bool
     */
    protected $allowNull;

    /**
     * ModelToPropertyValueTransformer constructor.
     * @param RegistryInterface $registry
     * @param string $modelClass
     * @param string $modelProperty
     * @param string $filterName
     * @param string $filterCondition
     * @param bool $allowNull
     */
    public function __construct(RegistryInterface $registry, string $modelClass, string $modelProperty, string $filterName, string $filterCondition, bool $allowNull = true)
    {
        $this->registry = $registry;
        $this->modelClass = $modelClass;
        $this->modelProperty = $modelProperty;
        $this->filterName = $filterName;
        $this->filterCondition = $filterCondition;
        $this->allowNull = $allowNull;
    }

    /**
     * @return EntityRepository
     */
    protected function getRepository(): EntityRepository
    {
        return $this->registry->getRepository($this->modelClass);
    }

    /**
     * {@inheritdoc}
     */
    public function transform($entity)
    {
        $value = null;

        if ($entity instanceof $this->modelClass) {
            $value = $entity->{'get'.ucfirst($this->modelProperty)}();
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
        if ($repo instanceof EntityRepositoryInterface && $repo->getPreConfiguredFilters()->hasFilter($this->filterName)) {
            $alias = $repo->getAlias();
            $builder = $repo->getQueryBuilder(['alias' => $alias]);
            $filter = $repo
                ->getPreConfiguredFilters()
                ->getFilter($this->filterName)
                ->setup($builder, [
                    'condition' => $this->filterCondition ,
                    'value' => $value,
                ], $alias)
            ;
            $builder->andWhere($filter->buildWhereCondition($builder));
            $result = $builder->getQuery()->getResult();
            $entity = null;
            if (!is_array($result)) {
                $entity = $result;
            } else if (count($result) > 0) {
                $entity = array_shift($result);
            }
            if (!$entity) {
                $entity = $this->createNewEntity($value);
            }
            if (!$entity instanceof $this->modelClass) {
                throw new TransformationFailedException(sprintf('The entity with %s "%s" could not be found', $this->modelProperty, $value));
            }
            return $entity;

        } else {
            throw new TransformationFailedException(sprintf('Unable to find the entity from repository %s', get_class($repo)));
        }
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
            $entity->{'set'.ucfirst($this->modelProperty)}($value);
            $this->registry->getManager()->persist($entity);
            $this->registry->getManager()->flush();
            return $entity;
        }
        throw new TransformationFailedException(sprintf('Unable to create new entity from repository %s', get_class($this->getRepository())));
    }
}
