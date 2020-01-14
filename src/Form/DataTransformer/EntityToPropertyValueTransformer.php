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
     * @var bool
     */
    protected $createEntityIfNotExists;

    /**
     * @var bool
     */
    protected $multiple;

    /**
     * @var string
     */
    protected $multipleDataType;

    /**
     * @var bool
     */
    protected $allowNull;

    /**
     * EntityToPropertyValueTransformer constructor.
     * @param ManagerRegistry $registry
     * @param string $entityClass
     * @param string $entityProperty
     * @param bool $multiple
     * @param string|null $multipleDataType
     * @param bool $allowNull
     * @param bool $createEntityIfNotExists
     */
    public function __construct(ManagerRegistry $registry, string $entityClass, string $entityProperty, bool $multiple = false, string $multipleDataType = null, bool $allowNull = true, bool $createEntityIfNotExists = true)
    {
        $this->registry = $registry;
        $this->entityClass = $entityClass;
        $this->entityProperty = $entityProperty;
        $this->multiple = $multiple;
        $this->multipleDataType = $multipleDataType === self::MULTIPLE_DATA_TYPE_ARRAY ? self::MULTIPLE_DATA_TYPE_ARRAY : self::MULTIPLE_DATA_TYPE_COLLECTION;
        $this->allowNull = $allowNull;
        $this->createEntityIfNotExists = $createEntityIfNotExists;
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
        $result = $this->getRepository()->findBy([$this->entityProperty => $value]);
        $entity = null;
        if ($result instanceof $this->entityClass) {
            $entity = $result;
        } else if (count($result) > 0) {
            $entity = array_shift($result);
        }
        if (!$entity && $this->createEntityIfNotExists) {
            $entity = $this->createNewEntity($value);
        }
        if (!$entity instanceof $this->entityClass) {
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
