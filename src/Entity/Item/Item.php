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

use Doctrine\ORM\Mapping as ORM;
use Dmytrof\ModelsManagementBundle\Model\Traits\TimestampableEntityTrait;
use Dmytrof\ImportBundle\{Model\Item as Model, Entity\Item\Repository};

/**
 * @ORM\Table(name="dmytrof_import_item", indexes={
 *     @ORM\Index(name="dmytrof_import_item_status_id", columns={"status_id", "task_id"}),
 *     @ORM\Index(name="dmytrof_import_item_entry_id", columns={"task_id", "entry_id"}),
 * })
 * @ORM\Entity(repositoryClass=Repository::class)
 */
class Item extends Model
{
    use TimestampableEntityTrait;

    /**
     * @ORM\Id
     * @ORM\Column(type="string")
     */
    protected $id;

    /**
     * @ORM\Column(type="integer", nullable=false)
     */
    protected $taskId;

    /**
     * @ORM\Column(type="string", nullable=false)
     */
    protected $entryId;

    /**
     * @ORM\Column(type="integer", nullable=false)
     */
    protected $statusId;

    /**
     * @ORM\Column(type="string", nullable=false)
     */
    protected $dataHash;

    /**
     * @ORM\Column(type="json", nullable=false)
     */
    protected $data;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    protected $errors;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $configHash;

    /**
     * @ORM\Column(type="json", nullable=true)
     */
    protected $target;
}