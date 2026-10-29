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

use Dmytrof\ModelsManagementBundle\Model\Traits\TimestampableEntityTrait;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Blameable\Traits\BlameableEntity;
use Dmytrof\ImportBundle\Model\Task as Model;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: Repository::class)]
#[ORM\Table(name: 'dmytrof_import_task')]
#[ORM\UniqueConstraint(name: 'dmytrof_import_task_code', columns: ['code'])]
#[ORM\Index(name: 'dmytrof_import_task_importer_code_idx', columns: ['importer_code'])]
#[ORM\Index(name: 'dmytrof_import_task_reader_code_idx', columns: ['reader_code'])]
#[UniqueEntity(fields: ['code'], ignoreNull: true)]
class Task extends Model
{
    use BlameableEntity,
        TimestampableEntityTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(type: 'string', length: 255, nullable: false)]
    protected ?string $title = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    protected ?string $code = null;

    #[ORM\Column(type: 'text', nullable: true)]
    protected ?string $link = null;

    #[ORM\Column(type: 'boolean', nullable: false, options: ['default' => false])]
    protected bool $paginatedLink = false;

    #[ORM\Column(type: 'string', nullable: true)]
    protected ?string $pageParameterInLink = null;

    #[ORM\Column(type: 'smallint', nullable: true)]
    protected ?int $firstPageValue = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    protected ?int $period = null;

    #[ORM\Column(type: 'boolean', nullable: false, options: ['default' => false])]
    protected bool $inProgress = false;

    #[ORM\Column(type: 'datetime', nullable: true)]
    protected ?\DateTime $importedAt = null;

    #[ORM\Column(type: 'string', length: 255, nullable: false)]
    protected ?string $importerCode = null;

    #[ORM\Column(name: 'importer_options', type: 'json', nullable: true)]
    protected ?array $importerOptionsArr = null;

    #[ORM\Column(type: 'string', length: 255, nullable: false)]
    protected ?string $readerCode = null;

    #[ORM\Column(name: 'reader_options', type: 'json', nullable: true)]
    protected ?array $readerOptionsArr = null;

    // Typed as in ActiveModelTrait of ModelsManagementBundle
    #[ORM\Column(type: 'boolean', nullable: false, options: ['default' => true])]
    protected bool $active = true;
}
