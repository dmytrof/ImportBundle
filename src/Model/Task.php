<?php

/*
 * This file is part of the DmytrofImportBundle package.
 *
 * (c) Dmytro Feshchenko <dmytro.feshchenko@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dmytrof\ImportBundle\Model;

use Monolog\Logger;
use Dmytrof\ModelsManagementBundle\Model\{SimpleModelInterface, Traits\SimpleModelTrait};
use Dmytrof\ModelsManagementBundle\Model\{ActiveModelInterface, Traits\ActiveModelTrait};
use Dmytrof\ImportBundle\Event\{PreImportTaskEvent, PostImportTaskEvent};
use Dmytrof\ImportBundle\Exception\{ImporterException, ReaderException, TaskException};
use Dmytrof\ImportBundle\Importer\{ImporterInterface, Options\ImporterOptionsInterface};
use Dmytrof\ImportBundle\Manager\TaskManager;
use Dmytrof\ImportBundle\Reader\{Options\ReaderOptionsInterface, ReaderInterface};
use Dmytrof\ImportBundle\Service\{ImportersContainer, ReadersContainer};
use Symfony\Component\Console\{Output\OutputInterface, Style\SymfonyStyle};
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class Task implements SimpleModelInterface, ActiveModelInterface, \SplObserver
{
    use SimpleModelTrait,
        ActiveModelTrait;

    public const VALIDATION_GROUP_LINK_AND_PARSER = 'LinkAndParser';

    public const PAGE_PARAMETER_IN_LINK = '{page}';

    public const PERIOD_EVERY_HOUR      = 3600;
    public const PERIOD_EVERY_4_HOURS   = 14400;
    public const PERIOD_EVERY_8_HOURS   = 28800;
    public const PERIOD_EVERY_12_HOURS  = 43200;
    public const PERIOD_EVERY_24_HOURS  = 86400;

    public const PERIODS = [
        self::PERIOD_EVERY_HOUR     => 'label.import_task.periods.every_hour',
        self::PERIOD_EVERY_4_HOURS  => 'label.import_task.periods.every_4_hours',
        self::PERIOD_EVERY_8_HOURS  => 'label.import_task.periods.every_8_hours',
        self::PERIOD_EVERY_12_HOURS => 'label.import_task.periods.every_12_hours',
        self::PERIOD_EVERY_24_HOURS => 'label.import_task.periods.every_24_hours',
    ];
    public const PERIOD_CHOICES = [
        self::PERIOD_EVERY_HOUR,
        self::PERIOD_EVERY_4_HOURS,
        self::PERIOD_EVERY_8_HOURS,
        self::PERIOD_EVERY_12_HOURS,
        self::PERIOD_EVERY_24_HOURS,
    ];

    /**
     * @var ImportersContainer
     */
    protected $importersContainer;

    /**
     * @var ReadersContainer
     */
    protected $readersContainer;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * ID
     * @var int
     */
    protected $id;

    /**
     * Title
     * @var string
     *
     * @Assert\NotBlank
     * @Assert\LessThanOrEqual(250)
     */
    protected $title;

    /**
     * Unique code if needed
     * @var string
     *
     * @Assert\LessThanOrEqual(250)
     */
    protected $code;

    /**
     * Feed link
     * @var string
     *
     * @Assert\NotBlank(groups={Task::VALIDATION_GROUP_LINK_AND_PARSER})
     */
    protected $link;

    /**
     * @var bool
     */
    protected $paginatedLink;

    /**
     * @var string
     */
    protected $pageParameterInLink;

    /**
     * @var integer
     */
    protected $firstPageValue;

    /**
     * Period
     * @var int
     *
     * @Assert\Choice(Task::PERIOD_CHOICES)
     */
    protected $period;

    /**
     * In progress now
     * @var bool
     */
    protected $inProgress;

    /**
     * Date & time of last import
     * @var \DateTime
     */
    protected $importedAt;

    /**
     * Import statistics
     * @var ImportStatistics
     */
    protected $importStatistics;

    /**
     * Import statistics array
     * @var array
     */
    protected $importStatisticsArr;

    /**
     * Importer code
     * @var string
     *
     * @Assert\NotBlank
     */
    protected $importerCode;

    /**
     * Importer options array
     * @var array
     */
    protected $importerOptionsArr;

    /**
     * Importer options
     * @var ImporterOptionsInterface
     *
     * @Assert\Valid
     */
    protected $importerOptions;

    /**
     * @var ImporterInterface
     */
    protected $importer;

    /**
     * @var string
     */
    protected $importerOptionsHash;

    /**
     * Reader code
     * @var string
     *
     * @Assert\NotBlank(groups={Task::VALIDATION_GROUP_LINK_AND_PARSER})
     */
    protected $readerCode;

    /**
     * Reader options array
     * @var array
     */
    protected $readerOptionsArr;

    /**
     * Reader options
     * @var ReaderOptionsInterface
     *
     * @Assert\Valid(groups={Task::VALIDATION_GROUP_LINK_AND_PARSER})
     */
    protected $readerOptions;

    /**
     * @var ReaderInterface
     */
    protected $reader;

    /**
     * @var bool
     */
    protected $forceImport = false;

    /**
     * Task constructor.
     */
    public function __construct()
    {
        $this->setInProgress(false);
        $this->setActive(true);
        $this->setPaginatedLink(false);
        $this->setPageParameterInLink(static::PAGE_PARAMETER_IN_LINK);
        $this->setFirstPageValue(1);
    }

    public function __clone()
    {
        $this->setInProgress(false);
        $this->setActive(true);
    }

    /**
     * Returns importers container
     * @return ImportersContainer|null
     */
    public function getImportersContainer(): ?ImportersContainer
    {
        return $this->importersContainer;
    }

    /**
     * Sets importers container
     * @param ImportersContainer|null $importersContainer
     * @return Task
     */
    public function setImportersContainer(?ImportersContainer $importersContainer): self
    {
        $this->importersContainer = $importersContainer;
        return $this;
    }

    /**
     * Returns readers container
     * @return ReadersContainer|null
     */
    public function getReadersContainer(): ?ReadersContainer
    {
        return $this->readersContainer;
    }

    /**
     * Sets readers container
     * @param ReadersContainer|null $readersContainer
     * @return Task
     */
    public function setReadersContainer(?ReadersContainer $readersContainer): self
    {
        $this->readersContainer = $readersContainer;
        return $this;
    }

    /**
     * Returns event dispatcher
     * @return null|EventDispatcherInterface
     */
    public function getEventDispatcher(): ?EventDispatcherInterface
    {
        return $this->eventDispatcher;
    }

    /**
     * Sets event dispatcher
     * @param null|EventDispatcherInterface $eventDispatcher
     * @return Task
     */
    public function setEventDispatcher(?EventDispatcherInterface $eventDispatcher): self
    {
        $this->eventDispatcher = $eventDispatcher;
        return $this;
    }

    /**
     * Returns array of periods
     * @return array
     */
    public static function getPeriods()
    {
        return array_keys(static::PERIODS);
    }

    /**
     * Returns array of periods titles
     * @return array
     */
    public static function getPeriodsTitles()
    {
        return static::PERIODS;
    }

    /**
     * @inheritDoc
     */
    public function getModelTitle(): string
    {
        return $this->getTitle().' (ID: '.$this->getId().')';
    }

    /**
     * Sets title
     * @param null|string $title
     * @return Task
     */
    public function setTitle(?string $title): self
    {
        $this->title = $title;
        return $this;
    }

    /**
     * Returns title
     * @return null|string
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * Returns code
     * @return string|null
     */
    public function getCode(): ?string
    {
        return $this->code;
    }

    /**
     * Returns code
     * @param string|null $code
     * @return Task
     */
    public function setCode(?string $code): self
    {
        $this->code = $code;
        return $this;
    }

    /**
     * Returns link
     * @return null|string
     */
    public function getLink(): ?string
    {
        return $this->link;
    }

    /**
     * Sets link
     * @param null|string $link
     * @return Task
     */
    public function setLink(?string $link): self
    {
        $this->link = $link;
        return $this;
    }

    /**
     * Checks if link is paginated
     * @return bool
     */
    public function isPaginatedLink(): bool
    {
        return (bool) $this->paginatedLink;
    }

    /**
     * Sets link paginated
     * @param bool|null $paginatedLink
     * @return Task
     */
    public function setPaginatedLink(?bool $paginatedLink): self
    {
        $this->paginatedLink = (bool) $paginatedLink;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getPageParameterInLink(): ?string
    {
        return $this->pageParameterInLink;
    }

    /**
     * Sets page parameter in link
     * @param string|null $pageParameterInLink
     * @return Task
     */
    public function setPageParameterInLink(?string $pageParameterInLink): self
    {
        $this->pageParameterInLink = $pageParameterInLink;
        return $this;
    }

    /**
     * Returns first page value
     * @return int
     */
    public function getFirstPageValue(): int
    {
        return $this->firstPageValue ?? 1;
    }

    /**
     * Sets first page value
     * @param int|null $firstPageValue
     * @return Task
     */
    public function setFirstPageValue(?int $firstPageValue): Task
    {
        $this->firstPageValue = (int) $firstPageValue;
        return $this;
    }

    /**
     * Returns period
     * @return int|null
     */
    public function getPeriod(): ?int
    {
        return $this->period;
    }

    /**
     * Sets period
     * @param int|null $period
     * @return Task
     */
    public function setPeriod(?int $period): self
    {
        $this->period = $period;
        return $this;
    }

    /**
     * Checks if task is in progress now
     * @return bool
     */
    public function isInProgress(): bool
    {
        return (bool) $this->inProgress;
    }

    /**
     * Sets in progress now
     * @param bool $inProgress
     * @return Task
     */
    public function setInProgress(?bool $inProgress = true): self
    {
        $this->inProgress = (bool) $inProgress;
        return $this;
    }

    /**
     * Returns imported at
     * @return \DateTime|null
     */
    public function getImportedAt(): ?\DateTime
    {
        return $this->importedAt;
    }

    /**
     * Sets imported at
     * @param \DateTime|null $importedAt
     * @return Task
     */
    public function setImportedAt(?\DateTime $importedAt): Task
    {
        $this->importedAt = $importedAt;
        return $this;
    }

    /**
     * Returns import statistics array
     * @return array|null
     */
    public function getImportStatisticsArr(): ?array
    {
        return $this->importStatisticsArr;
    }

    /**
     * Sets import statistics array
     * @param array|null $importStatisticsArr
     * @return Task
     */
    public function setImportStatisticsArr(?array $importStatisticsArr): self
    {
        $this->importStatisticsArr = $importStatisticsArr;
        return $this;
    }

    /**
     * Returns importer statistics
     * @return ImporterOptionsInterface|null
     */
    public function getImportStatistics(): ?ImportStatistics
    {
        if (is_null($this->importStatistics)) {
            $this->importStatistics = new ImportStatistics();
            $this->importStatistics->fromArray((array) $this->getImportStatisticsArr());
        }

        return $this->importStatistics;
    }

    /**
     * Sets import statistics
     * @param ImportStatistics|null $importStatistics
     * @return Task
     */
    protected function setImportStatistics(?ImportStatistics $importStatistics): self
    {
        $this->importStatistics = $importStatistics;
        $importStatisticsArr = $this->importStatistics ? $this->importStatistics->toArray() : null;
        $this->setImportStatisticsArr($importStatisticsArr);

        return $this;
    }

    /**
     * Returns import statistics array ans json
     * @return array|null
     */
    public function getImportStatisticsStr(): ?string
    {
        return json_encode($this->getImportStatisticsArr());
    }

    /**
     * Needs for form correct working only
     * @param null|string $importStatisticsStr
     * @return Task
     */
    public function setImportStatisticsStr(?string $importStatisticsStr): self
    {
        return $this;
    }

    /**
     * Returns importer code
     * @return null|string
     */
    public function getImporterCode(): ?string
    {
        return $this->importerCode;
    }

    /**
     * Sets importer code
     * @param null|string $importerCode
     * @return Task
     */
    public function setImporterCode(?string $importerCode): self
    {
        $this->importer = null;
        $this->importerCode = $importerCode;
        $this->updateImporterOptions($this->getImporter()->getOptions());

        return $this;
    }

    /**
     * Returns importer
     * @return ImporterInterface|null
     */
    public function getImporter(): ?ImporterInterface
    {
        if (is_null($this->importer) && $this->getImportersContainer()->has($this->getImporterCode())) {
            $this->importer = (clone $this->getImportersContainer()->get($this->getImporterCode()))
                ->setTask($this)
            ;
        }

        return $this->importer;
    }

    /**
     * Returns importer title
     * @return null|string
     */
    public function getImporterTitle(): ?string
    {
        return $this->getImporter() ? $this->getImporter()->getTitle() : null;
    }

    /**
     * Returns importer options array
     * @return array|null
     */
    public function getImporterOptionsArr(): ?array
    {
        return $this->importerOptionsArr;
    }

    /**
     * Sets importer options array
     * @param array|null $importerOptionsArr
     * @return Task
     */
    public function setImporterOptionsArr(?array $importerOptionsArr): self
    {
        $this->importerOptionsArr = $importerOptionsArr;
        $this->resetImporterOptionsHash();

        return $this;
    }

    /**
     * Returns importer options
     * @return ImporterOptionsInterface|null
     */
    public function getImporterOptions(): ?ImporterOptionsInterface
    {
        if (is_null($this->importerOptions) && $this->getImporter() && $this->getImporter()->hasOptions()) {
            $optionsClass = $this->getImporter()->getOptionsClass();
            $this->importerOptions = new $optionsClass();
            $this->importerOptions->fromArray((array) $this->getImporterOptionsArr());
            $this->importerOptions
                ->prepareImportableFieldsOptions($this->getImporter()->getImportableFields())
                ->attach($this);
        }

        return $this->importerOptions;
    }

    /**
     * Sets importer options
     * @param ImporterOptionsInterface|null $importerOptions
     * @return Task
     */
    protected function setImporterOptions(?ImporterOptionsInterface $importerOptions): self
    {
        $this->importerOptions = $importerOptions;
        $importerOptionsArr = $this->getImporter() && $this->getImporter()->hasOptions() && $this->importerOptions ? $this->importerOptions->toArray() : null;
        $this->setImporterOptionsArr($importerOptionsArr);

        return $this;
    }

    /**
     * Updates importer options
     * @param ImporterOptionsInterface|null $options
     * @return Task
     */
    public function updateImporterOptions(?ImporterOptionsInterface $options): self
    {
        $this->setImporterOptions($options);
        return $this;
    }

    /**
     * Returns importer options hash
     * @return string|null
     */
    public function getImporterOptionsHash(): ?string
    {
        if (is_null($this->importerOptionsHash)) {
            $this->importerOptionsHash = !is_null($this->getImporterOptionsArr()) ? sha1(json_encode(array_intersect_key($this->getImporterOptionsArr(), $this->getImporterOptions()->getOptionsHashScheme()))) : null;
        }

        return $this->importerOptionsHash;
    }

    /**
     * Resets importer options hash
     * @return Task
     */
    protected function resetImporterOptionsHash(): self
    {
        $this->importerOptionsHash = null;
        return $this;
    }

    /**
     * Importer definition
     * @return ImporterDefinition
     */
    public function getImporterDefinition(): ImporterDefinition
    {
        return new ImporterDefinition($this);
    }

    /**
     * Empty method for form
     * @param ImporterDefinition $definition
     * @return Task
     */
    public function setImporterDefinition(ImporterDefinition $definition): self
    {
        return $this;
    }

    /**
     * Returns reader code
     * @return null|string
     */
    public function getReaderCode(): ?string
    {
        return $this->readerCode;
    }

    /**
     * Sets reader code
     * @param null|string $readerCode
     * @return Task
     */
    public function setReaderCode(?string $readerCode): self
    {
        $this->reader = null;
        $this->readerCode = $readerCode;
        $this->updateReaderOptions($this->getReader() ? $this->getReader()->getOptions()  : null);

        return $this;
    }

    /**
     * Validates reader code
     * @param ExecutionContextInterface $context
     *
     * @Assert\Callback(groups={Task::VALIDATION_GROUP_LINK_AND_PARSER})
     */
    public function validateReaderCode(ExecutionContextInterface $context)
    {
        if (!$this->getReadersContainer()->has($this->getReaderCode())) {
            $context
                ->buildViolation('Undefined reader code')
                ->atPath('readerCode')
                ->addViolation()
            ;
        }
    }

    /**
     * Returns reader
     * @return ReaderInterface|null
     */
    public function getReader(): ?ReaderInterface
    {
        if (is_null($this->reader) && $this->getReadersContainer()->has($this->getReaderCode())) {
            $this->reader = (clone $this->getReadersContainer()->get($this->getReaderCode()))
                ->setTask($this)
            ;
        }

        return $this->reader;
    }

    /**
     * Returns reader title
     * @return null|string
     */
    public function getReaderTitle(): ?string
    {
        return $this->getReader() ? $this->getReader()->getTitle() : null;
    }

    /**
     * Returns reader options array
     * @return array|null
     */
    public function getReaderOptionsArr(): ?array
    {
        return $this->readerOptionsArr;
    }

    /**
     * Sets reader options array
     * @param array|null $readerOptionsArr
     * @return Task
     */
    public function setReaderOptionsArr(?array $readerOptionsArr): self
    {
        $this->readerOptionsArr = $readerOptionsArr;
        return $this;
    }

    /**
     * Returns reader options
     * @return ReaderOptionsInterface|null
     */
    public function getReaderOptions(): ?ReaderOptionsInterface
    {
        if (is_null($this->readerOptions) && $this->getReader() && $this->getReader()->hasOptions()) {
            $optionsClass = $this->getReader()->getOptionsClass();
            $this->readerOptions = new $optionsClass();
            $this->readerOptions->fromArray((array) $this->getReaderOptionsArr());
            $this->readerOptions->attach($this);
        }

        return $this->readerOptions;
    }

    /**
     * Sets reader options
     * @param ReaderOptionsInterface|null $readerOptions
     * @return Task
     */
    protected function setReaderOptions(?ReaderOptionsInterface $readerOptions): self
    {
        $this->readerOptions = $readerOptions;
        $readerOptionsArr = $this->getReader() && $this->getReader()->hasOptions() && $this->readerOptions ? $this->readerOptions->toArray() : null;
        $this->setReaderOptionsArr($readerOptionsArr);

        return $this;
    }

    /**
     * Updates reader options
     * @param ReaderOptionsInterface|null $options
     * @return Task
     */
    public function updateReaderOptions(?ReaderOptionsInterface $options): self
    {
        $this->setReaderOptions($options);
        return $this;
    }

    /**
     * Reader definition
     * @return ReaderDefinition
     */
    public function getReaderDefinition(): ReaderDefinition
    {
        return new ReaderDefinition($this);
    }

    /**
     * Empty method for form
     * @param ReaderDefinition $definition
     * @return Task
     */
    public function setReaderDefinition(ReaderDefinition $definition): self
    {
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function update(\SplSubject $subject): void
    {
        if ($subject instanceof ImporterOptionsInterface) {
            $this->updateImporterOptions($subject);
        } else if ($subject instanceof ReaderOptionsInterface) {
            $this->updateReaderOptions($subject);
        }
    }

    /**
     * Imports data from resource
     * @param TaskManager $manager
     * @param SymfonyStyle|null $io
     * @param Logger|null $logger
     * @param array $options
     * @return $this
     */
    public function importData(TaskManager $manager, ?SymfonyStyle $io = null, ?Logger $logger = null, array $options = []): self
    {
        try {
            $io->title($this->getModelTitle());
            $logger->info('IMPORTING: '.$this->getModelTitle());
            $this
                ->setInProgress(true)
                ->setImportedAt(new \DateTime())
            ;
            $manager->save($this);
            $this->getEventDispatcher()->dispatch(new PreImportTaskEvent($this));

            $importer = $this->getImporter();
            $importer
                ->setOutput($io)
                ->setLogger($logger)
                ->importTask($this, $options);

            $importer->getLogger()->info('Importing finished!');
            $importer->getOutput()->success('Importing finished!');
            /** @var Task $task */
            $task = $manager->getManager()->merge($this);
            $task
                ->setInProgress(false)
                ->setImportStatistics($importer->getImportStatistics())
            ;
            $manager->save($task);
            $this->getEventDispatcher()->dispatch(new PostImportTaskEvent($task));
            return $task;
        } catch (ReaderException|ImporterException $e) {
            if ($io || $logger) {
                if ($logger) {
                    $logger->error($e->getMessage());
                }
                if ($io) {
                    $io->error($e->getMessage());
                }
                return $this->getId() ? $this : $manager->getManager()->merge($this);
            } else {
                throw $e;
            }
        }
    }

    /**
     * Returns prepared link
     * @param int $page
     * @return ImportedData
     */
    public function getPreparedLink(int $page = 1): string
    {
        $link = $this->getLink();
        if ($this->isPaginatedLink()) {
            $link = str_replace($this->getPageParameterInLink(), $page, $link);
            if ($link === $this->getLink()) { // link is without page parameter
                throw new TaskException(sprintf('Paginated link has no page parameter "%s"', $this->getPageParameterInLink()));
            }
        }
        return $link;
    }

    /**
     * Returns data from link
     * @param bool $exampleData
     * @param string|null $link
     * @return ImportedData
     */
    public function getDataFromLink(bool $exampleData = false, string $link = null): ImportedData
    {
        return $this->getReader()->getDataFromLink($link ?? $this->getLink(), ['exampleData' => $exampleData]);
    }

    /**
     * Checks if force import
     * @return bool
     */
    public function isForceImport(): bool
    {
        return (bool) $this->forceImport;
    }

    /**
     * Sets force import
     * @param bool|null $forceImport
     * @return Task
     */
    public function setForceImport(?bool $forceImport = true): Task
    {
        $this->forceImport = (bool) $forceImport;
        return $this;
    }
}