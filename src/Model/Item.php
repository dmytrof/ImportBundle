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
use Dmytrof\ModelsManagementBundle\Model\{ActiveModelInterface, Traits\ActiveModelTrait};
use Dmytrof\ModelsManagementBundle\Model\{SimpleModelInterface, Traits\SimpleModelTrait};
use Dmytrof\ModelsManagementBundle\Model\{TargetedModelInterface, Traits\TargetedModelTrait};
use Dmytrof\ImportBundle\Exception\{ImporterException, ItemException};
use Dmytrof\ImportBundle\Manager\{ItemManager, TaskManager};
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Validator\Constraints as Assert;

class Item implements SimpleModelInterface, ActiveModelInterface, TargetedModelInterface
{
    use TargetedModelTrait,
        SimpleModelTrait,
        ActiveModelTrait;

    public const STATUS_SKIPPED     = 1;
    public const STATUS_CREATED     = 2;
    public const STATUS_UPDATED     = 3;
    public const STATUS_DELETED     = 4;
    public const STATUS_ERROR       = 5;
    public const STATUS_SCHEDULED   = 6;
    public const STATUS_DUPLICATE   = 7;
    public const STATUS_DATA_ERROR  = 8;

    const STATUSES = [
        self::STATUS_SKIPPED    => 'label.import_item.statuses.skipped',
        self::STATUS_CREATED    => 'label.import_item.statuses.created',
        self::STATUS_UPDATED    => 'label.import_item.statuses.updated',
        self::STATUS_DELETED    => 'label.import_item.statuses.deleted',
        self::STATUS_ERROR      => 'label.import_item.statuses.error',
        self::STATUS_SCHEDULED  => 'label.import_item.statuses.scheduled',
        self::STATUS_DUPLICATE  => 'label.import_item.statuses.duplicate',
        self::STATUS_DATA_ERROR => 'label.import_item.statuses.data_error',
    ];

    /**
     * @var TaskManager
     */
    protected $taskManager;

    /**
     * ID
     * @var string
     */
    protected $id;

    /**
     * Task id
     * @var integer
     *
     * @Assert\NotBlank
     */
    protected $taskId;

    /**
     * Entry id
     * @var string
     *
     * @Assert\NotBlank
     */
    protected $entryId;

    /**
     * Status
     * @var int
     *
     * @Assert\NotBlank
     */
    protected $statusId;

    /**
     * Data hash
     * @var string
     *
     * @Assert\NotBlank
     */
    protected $dataHash;

    /**
     * Config hash
     * @var string
     *
     * @Assert\NotBlank
     */
    protected $configHash;

    /**
     * Entry data
     * @var array
     *
     * @Assert\Count(min=1)
     */
    protected $data;

    /**
     * Errors
     * @var string
     */
    protected $errors;

    /**
     * @var bool
     */
    protected $forceImport = false;

    /**
     * Returns array of statuses
     * @return array
     */
    public static function getStatuses()
    {
        return array_keys(static::STATUSES);
    }

    /**
     * Returns array of statuses titles
     * @return array
     */
    public static function getStatusesTitles()
    {
        return static::STATUSES;
    }

    /**
     * @param int|null $taskId
     * @param string|null $entryId
     * @param string|null $dataHash
     * @return string
     */
    public static function generateId(?int $taskId, ?string $entryId, ?string $dataHash): string
    {
        return join('_', [$taskId, $entryId, substr($dataHash, 0, 8)]);
    }

    /**
     * Generates data hash
     * @param array|null $data
     * @return null|string
     */
    public static function generateDataHash(?array $data): ?string
    {
        return !is_null($data) ? sha1(json_encode($data)) : null;
    }

    /**
     * Returns task manager
     * @return TaskManager|null
     */
    public function getTaskManager(): ?TaskManager
    {
        return $this->taskManager;
    }

    /**
     * Sets task manager
     * @param TaskManager|null $taskManager
     * @return Item
     */
    public function setTaskManager(?TaskManager $taskManager): self
    {
        $this->taskManager = $taskManager;
        return $this;
    }

    /**
     * Generates id
     * @return Item
     */
    protected function _generateId(): self
    {
        $this->id = $this->generateId(
            $this->getTaskId(),
            $this->getEntryId(),
            $this->getDataHash()
        );
        return $this;
    }

    /**
     * Returns task
     * @return Task|null
     */
    public function getTaskId(): ?int
    {
        return $this->taskId;
    }

    /**
     * Sets task id
     * @param int|null $taskId
     * @return Item
     */
    public function setTaskId(?int $taskId): self
    {
        $this->taskId = $taskId;
        $this->_generateId();
        return $this;
    }

    /**
     * Returns task
     * @return Task
     */
    public function getTask(): Task
    {
        if (!$this->getTaskManager()) {
            throw new ItemException('Undefined task manager');
        }
        return $this->getTaskManager()->getTask($this->getTaskId());
    }

    /**
     * Returns task title
     * @return string|null
     */
    public function getTaskTitle(): ?string
    {
        return $this->getTask() ? $this->getTask()->getModelTitle() : null;
    }

    /**
     * @param string|null $taskTitle
     * @return Item
     */
    public function setTaskTitle(?string $taskTitle): self
    {
        // Unable to set task title here
        return $this;
    }

    /**
     * Returns entry id
     * @return null|string
     */
    public function getEntryId(): ?string
    {
        return $this->entryId;
    }

    /**
     * Sets entry id
     * @param null|string $entryId
     * @return Item
     */
    public function setEntryId(?string $entryId): self
    {
        $this->entryId = $entryId;
        $this->_generateId();
        return $this;
    }

    /**
     * Returns status ID
     * @return int|null
     */
    public function getStatusId(): ?int
    {
        return $this->statusId;
    }

    /**
     * Sets status ID
     * @param int|null $statusId
     * @return Item
     */
    public function setStatusId(?int $statusId): self
    {
        $this->statusId = $statusId;
        $this->resetErrors();
        return $this;
    }

    /**
     * Returns status title
     * @return string
     */
    public function getStatusTitle(): string
    {
        return isset($this->getStatusesTitles()[$this->getStatusId()]) ? $this->getStatusesTitles()[$this->getStatusId()] : 'Undefined';
    }

    /**
     * Returns config hash
     * @return null|string
     */
    public function getConfigHash(): ?string
    {
        return $this->configHash;
    }

    /**
     * Sets config hash
     * @param string $configHash
     * @return Item
     */
    public function setConfigHash(string $configHash): self
    {
        $this->configHash = $configHash;
        return $this;
    }


    /**
     * Returns data hash
     * @return null|string
     */
    public function getDataHash(): ?string
    {
        return $this->dataHash;
    }

    /**
     * Sets data hash
     * @param null|string $dataHash
     * @return Item
     */
    protected function setDataHash(?string $dataHash): self
    {
        $this->dataHash = $dataHash;
        $this->_generateId();
        return $this;
    }

    /**
     * Returns data
     * @return array|null
     */
    public function getData(): ?array
    {
        return $this->data;
    }

    /**
     * Sets data
     * @param array|null $data
     * @return Item
     */
    public function setData(?array $data): self
    {
        $this->data = $data;
        $this->setDataHash($this->generateDataHash($data));
        return $this;
    }

    /**
     * Returns data value by key
     * @param string $key
     * @param null $defaultValue
     * @return mixed|null
     */
    public function getDataValue(string $key, $defaultValue = null)
    {
        return isset($this->data[$key]) ? $this->data[$key] : $defaultValue;
    }

    /**
     * Returns errors
     * @return array|null
     */
    public function getErrors(): ?array
    {
        return $this->errors ? json_decode($this->errors, true) : null;
    }

    /**
     * Sets errors
     * @param array|null $errors
     * @return Item
     */
    public function setErrors(?array $errors = []): self
    {
        $this->errors = $errors ? json_encode($errors) : null;
        return $this;
    }

    /**
     * Resets errors
     * @return Item
     */
    public function resetErrors(): self
    {
        $this->errors = null;
        return $this;
    }

    /**
     * Returns errors as string
     * @return string|null
     */
    public function getErrorsStr(): ?string
    {
        return $this->errors;
    }

    /**
     * @param string|null $errors
     * @return Item
     */
    public function setErrorsStr(?string $errors): self
    {
        // Unable to set errors as string here
        return $this;
    }

    /**
     * Checks if item is new
     * @return bool
     */
    public function isNew(): bool
    {
        return $this->isModelNew() || !$this->getDataHash();
    }

    /**
     * Imports data from resource
     * @param ItemManager $manager
     * @param SymfonyStyle|null $io
     * @param Logger|null $logger
     * @return Item
     */
    public function importScheduledData(ItemManager $manager, ?SymfonyStyle $io = null, ?Logger $logger = null): self
    {
        try {
            $io->title($this->getModelTitle());
            $logger->info('IMPORTING: '.$this->getModelTitle());

            $importer = $this->getTask()->getImporter();
            $importer
                ->setOutput($io)
                ->setLogger($logger)
                ->importItem($this);

            switch($this->getStatusId()) {
                case self::STATUS_CREATED:
                case self::STATUS_UPDATED:
                    $loggerMethod = 'info';
                    $outputMethod = 'success';
                    break;
                case self::STATUS_ERROR:
                case self::STATUS_DATA_ERROR:
                    $outputMethod = $loggerMethod = 'error';
                    break;
                case self::STATUS_DUPLICATE:
                    $outputMethod = $loggerMethod = 'warning';
                    break;
                case self::STATUS_SKIPPED:
                default:
                    $loggerMethod = 'info';
                    $outputMethod = 'text';
            }
            $importer->getLogger()->$loggerMethod('Imported! Status: '.$this->getStatusTitle());
            $importer->getOutput()->$outputMethod('Imported! Status: '.$this->getStatusTitle());

            return $this;
        } catch (ImporterException $e) {
            if ($io || $logger) {
                if ($logger) {
                    $logger->error($e->getMessage());
                }
                if ($io) {
                    $io->error($e->getMessage());
                }
                $item = $this->getId() ? $this : $manager->getManager()->merge($this);
                $item
                    ->setStatusId(self::STATUS_ERROR)
                    ->setErrors([$e->getMessage()])
                ;
                $manager->save($item);
                return $item;
            } else {
                throw $e;
            }
        }
    }

    /**
     * Returns form data
     * @return array
     */
    public function getFormData(): array
    {
        if (!$this->getTask() || !$this->getTask()->getImporter()) {
            return [];
        }
        return $this->getTask()->getImporter()->getImportFormData($this)->getData();
    }

    /**
     * Sets data from import form data
     * @param array|null $formData
     * @return Item
     */
    public function setFormData(?array $formData = []): self
    {
        $data = $this->getTask()->getImporter()->getDataFromImportFormData(new ImportFormData($formData), $this);
        $this->setData($data);
        return $this;
    }
}