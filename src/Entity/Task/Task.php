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
use Dmytrof\ImportBundle\{Model\Task as Model, Entity\Task\Repository};
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Table(name="dmytrof_import_task", uniqueConstraints={
 *     @ORM\UniqueConstraint(name="dmytrof_import_task_code", columns={"code"}),
 *  }, indexes={
 *     @ORM\Index(name="dmytrof_import_task_importer_code_idx",  columns={"importer_code"}),
 *     @ORM\Index(name="dmytrof_import_task_reader_code_idx",  columns={"reader_code"}),
 *  })
 * @ORM\Entity(repositoryClass=Repository::class)
 *
 * @UniqueEntity(fields={"code"}, ignoreNull=true)
 */
class Task extends Model
{
    use BlameableEntity,
        TimestampableEntityTrait;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="string", length=255, nullable=false)
     */
    protected $title;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $code;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    protected $link;

    /**
     * @ORM\Column(type="boolean", nullable=false, options={"default":false})
     */
    protected $paginatedLink;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $pageParameterInLink;

    /**
     * @ORM\Column(type="smallint", nullable=true)
     */
    protected $firstPageValue;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $period;

    /**
     * @ORM\Column(type="boolean", nullable=false, options={"default":false})
     */
    protected $inProgress;

    /**
     * @ORM\Column(name="import_statistics", type="json", nullable=true)
     */
    protected $importStatisticsArr;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $importedAt;

    /**
     * @ORM\Column(type="string", length=255, nullable=false)
     */
    protected $importerCode;

    /**
     * @ORM\Column(name="importer_options", type="json", nullable=true)
     */
    protected $importerOptionsArr;

    /**
     * @ORM\Column(type="string", length=255, nullable=false)
     */
    protected $readerCode;

    /**
     * @ORM\Column(name="reader_options", type="json", nullable=true)
     */
    protected $readerOptionsArr;

    /**
     * @ORM\Column(type="boolean", nullable=false, options={"default":true})
     */
    protected $active;
}