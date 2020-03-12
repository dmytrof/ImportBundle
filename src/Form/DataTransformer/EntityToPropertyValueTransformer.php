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
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Inflector\Inflector;
use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Component\Form\{DataTransformerInterface, Exception\TransformationFailedException};

class EntityToPropertyValueTransformer implements DataTransformerInterface
{
    public const MULTIPLE_DATA_TYPE_ARRAY = 'array';
    public const MULTIPLE_DATA_TYPE_COLLECTION = 'collection';

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
    protected $entityIdProperty = 'id';

    /**
     * @var bool
     */
    protected $createEntityIfNotExists = false;

    /**
     * @var bool
     */
    protected $multiple = false;

    /**
     * @var string
     */
    protected $multipleDataType = self::MULTIPLE_DATA_TYPE_COLLECTION;

    /**
     * @var bool
     */
    protected $allowNull = true;

    /**
     * @var bool
     */
    protected $nullOnException = false;

    /**
     * EntityToPropertyValueTransformer constructor.
     * @param ManagerRegistry $registry
     * @param string $entityClass
     * @param string $entityProperty
     */
    public function __construct(ManagerRegistry $registry, string $entityClass, string $entityProperty)
    {
        $this->registry = $registry;
        $this->entityClass = $entityClass;
        $this->entityProperty = $entityProperty;
    }

    /**
     * Sets entity ID property
     * @param string $entityIdProperty
     * @return EntityToPropertyValueTransformer
     */
    public function setEntityIdProperty(string $entityIdProperty): self
    {
        $this->entityIdProperty = $entityIdProperty;
        return $this;
    }

    /**
     * Sets create entity if not exists flag
     * @param bool $createEntityIfNotExists
     * @return EntityToPropertyValueTransformer
     */
    public function setCreateEntityIfNotExists(bool $createEntityIfNotExists = true): self
    {
        $this->createEntityIfNotExists = $createEntityIfNotExists;
        return $this;
    }

    /**
     * Sets multiple
     * @param bool $multiple
     * @return EntityToPropertyValueTransformer
     */
    public function setMultiple(bool $multiple = true): self
    {
        $this->multiple = $multiple;
        return $this;
    }

    /**
     * Sets multiple data type
     * @param string $multipleDataType
     * @return EntityToPropertyValueTransformer
     */
    public function setMultipleDataType(string $multipleDataType): self
    {
        $this->multipleDataType = $multipleDataType === self::MULTIPLE_DATA_TYPE_ARRAY ? self::MULTIPLE_DATA_TYPE_ARRAY : self::MULTIPLE_DATA_TYPE_COLLECTION;;
        return $this;
    }

    /**
     * Sets allow null
     * @param bool $allowNull
     * @return EntityToPropertyValueTransformer
     */
    public function setAllowNull(bool $allowNull = true): self
    {
        $this->allowNull = $allowNull;
        return $this;
    }

    /**
     * Sets null on exception
     * @param bool $nullOnException
     * @return EntityToPropertyValueTransformer
     */
    public function setNullOnException(bool $nullOnException = true): self
    {
        $this->nullOnException = $nullOnException;
        return $this;
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
     * Converts entity to string
     * @param $entity
     * @return string|null
     */
    protected function convertEntityToString($entity): ?string
    {
        if ($entity instanceof $this->entityClass) {
            return $entity->{'get'.Inflector::classify($this->entityProperty)}();
        }
        return null;
    }

    /**
     * Converts string to entity
     * @param $value
     * @return array|mixed|null
     */
    protected function convertStringToEntity($value)
    {
        $builder = $this->getRepository()->createQueryBuilder('a');
        $builder
            ->select('a.'.$this->entityIdProperty)
            ->where($builder->expr()->eq('a.'.$this->entityProperty, ':value'))
            ->setParameter('value', $value)
        ;
        $rows = $builder->getQuery()->getScalarResult();
        $result = null;
        if (is_array($rows) && isset($rows[0][$this->entityIdProperty])) {
            $result = $this->registry->getManagerForClass($this->entityClass)->getReference($this->entityClass, $rows[0][$this->entityIdProperty]);
        }

        $entity = null;
        if ($result instanceof $this->entityClass) {
            $entity = $result;
        }
        if (!$entity && $this->createEntityIfNotExists) {
            $entity = $this->createNewEntity($value);
        }
        if (!$entity instanceof $this->entityClass) {
            if ($this->nullOnException) {
                return null;
            }
            throw new TransformationFailedException(sprintf('The entity with %s "%s" could not be found', $this->entityProperty, $value));
        }
        return $entity;
    }

    /**
     * {@inheritdoc}
     */
    public function transform($entity)
    {
        if ($this->multiple) {
            $entity = is_iterable($entity) ? $entity : [$entity];
            $values = [];
            foreach ($entity as $_entity) {
                array_push($values, $this->convertEntityToString($_entity));
            }
            $values = array_filter($values, function ($v) { return !is_null($v);});

            return $this->multipleDataType === self::MULTIPLE_DATA_TYPE_ARRAY ? $values : new ArrayCollection($values);
        }

        return $this->convertEntityToString($entity);
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value)
    {
        if ($this->multiple) {
            $value = is_iterable($value) ? $value : [$value];
            $entities = [];
            foreach ($value as $val) {
                $val = $this->stringifyValue($val);
                try {
                    array_push($entities, $this->convertStringToEntity($val));
                } catch (TransformationFailedException $e) {
                }
                $entities = array_filter($entities, function ($v) { return !is_null($v);});
            }

            return $this->multipleDataType === self::MULTIPLE_DATA_TYPE_ARRAY ? $entities : new ArrayCollection($entities);
        } else {
            $value = $this->stringifyValue($value);

            if ($this->allowNull && empty($value)) {
                return null;
            } else if (!$this->allowNull && empty($value)) {
                throw new TransformationFailedException(sprintf('Empty value is not allowed'));
            }

            return $this->convertStringToEntity($value);
        }
    }

    /**
     * Creates new entity
     * @param string $value
     * @return mixed
     */
    protected function createNewEntity(string $value)
    {
        $entity = ($this->getRepository() instanceof EntityRepositoryInterface) ? $this->getRepository()->createNew() : new $this->entityClass();
        $entity->{'set'.Inflector::classify($this->entityProperty)}($value);
        $this->registry->getManager()->persist($entity);
        $this->registry->getManager()->flush();

        return $entity;
    }
}
