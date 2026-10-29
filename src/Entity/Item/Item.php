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
use Dmytrof\ImportBundle\Model\Item as Model;

#[ORM\Entity(repositoryClass: Repository::class)]
#[ORM\Table(name: 'dmytrof_import_item')]
#[ORM\Index(name: 'dmytrof_import_item_status_id', columns: ['status_id', 'task_id'])]
#[ORM\Index(name: 'dmytrof_import_item_entry_id', columns: ['task_id', 'entry_id'])]
class Item extends Model
{
    use TimestampableEntityTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'string')]
    protected ?string $id = null;

    #[ORM\Column(type: 'integer', nullable: false)]
    protected ?int $taskId = null;

    #[ORM\Column(type: 'string', nullable: false)]
    protected ?string $entryId = null;

    #[ORM\Column(type: 'integer', nullable: false)]
    protected ?int $statusId = null;

    #[ORM\Column(type: 'string', nullable: false)]
    protected ?string $dataHash = null;

    #[ORM\Column(type: 'json', nullable: false)]
    protected ?array $data = null;

    #[ORM\Column(type: 'text', nullable: true)]
    protected ?string $errors = null;

    #[ORM\Column(type: 'string', nullable: true)]
    protected ?string $configHash = null;

    #[ORM\Column(type: 'json', nullable: true)]
    protected ?array $target = null;
}
