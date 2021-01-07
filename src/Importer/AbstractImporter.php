<?php

/*
 * This file is part of the DmytrofImportBundle package.
 *
 * (c) Dmytro Feshchenko <dmytro.feshchenko@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dmytrof\ImportBundle\Importer;

use Monolog\Logger;
use Psr\Log\{LoggerInterface, NullLogger};
use Symfony\Component\Console\{Input\ArrayInput, Style\SymfonyStyle};
use Symfony\Component\Console\Output\{NullOutput, OutputInterface};
use Symfony\Component\Form\{Form, FormInterface};
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;
use Dmytrof\ModelsManagementBundle\Exception\{FormErrorsException, InvalidTargetException};
use Dmytrof\ModelsManagementBundle\{Manager\AbstractDoctrineManager,
    Manager\AbstractManager,
    Model\SimpleModelInterface};
use Dmytrof\ImportBundle\{Exception\ImporterException,
    Exception\SkippedItemException,
    Manager\ItemManager,
    Form\Type\Importer\ImporterOptionsType};
use Dmytrof\ImportBundle\Importer\Options\{ImporterOptions, ImporterOptionsInterface};
use Dmytrof\ImportBundle\Model\{ImportableField, ImportableFields, ImportableFieldsOptions, ImportedData, ImportFormData, ImportStatistics, Item, Task};

abstract class AbstractImporter implements ImporterInterface
{
    public const CODE = null;
    public const TITLE = null;
    public const TRANSLATIONS_DOMAIN = null;
    public const OPTIONS_CLASS = ImporterOptions::class;
    public const OPTIONS_FORM_CLASS = ImporterOptionsType::class;
    public const IMPORTER_FORM_CLASS = null;
    public const IMPORTER_FORM_OPTIONS = [];
    public const COMPOUND_FIELD_NAME_DELIMITER = '.';
    public const BUTCH_LENGTH = 10;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var Task
     */
    protected $task;

    /**
     * @var AbstractManager
     */
    protected $manager;

    /**
     * @var SymfonyStyle
     */
    protected $output;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var ItemManager
     */
    protected $itemManager;

    /**
     * @var ImportStatistics
     */
    protected $importStatistics;

    /**
     * @var ImportableFields
     */
    protected $importableFieldsWithOptions;

    /**
     * @var array
     */
    protected $processedEntriesIds = [];

    /**
     * {@inheritdoc}
     */
    public static function getCode(): string
    {
        return static::CODE;
    }

    /**
     * {@inheritdoc}
     */
    public static function getOptionsClass(): ?string
    {
        return static::OPTIONS_CLASS;
    }

    /**
     * {@inheritdoc}
     */
    public static function getOptionsFormClass(): ?string
    {
        return static::OPTIONS_FORM_CLASS;
    }

    /**
     * {@inheritdoc}
     */
    public static function getImporterFormClass(): ?string
    {
        return static::IMPORTER_FORM_CLASS;
    }

    /**
     * {@inheritdoc}
     */
    public function getImporterFormOptions(): array
    {
        return static::IMPORTER_FORM_OPTIONS;
    }

    /**
     * Returns translations domain
     * @return string|null
     */
    public function getTranslationsDomain(): ?string
    {
        return static::TRANSLATIONS_DOMAIN;
    }

    /**
     * Returns butch length
     * @return int
     */
    public static function getButchLength(): int
    {
        return static::BUTCH_LENGTH;
    }

    /**
     * {@inheritdoc}
     */
    public static function hasOptions(): bool
    {
        return static::getOptionsClass() !== null;
    }

    /**
     * Returns manager
     * @return AbstractManager
     */
    public function getManager(): AbstractManager
    {
        if (is_null($this->manager)) {
            throw new ImporterException(sprintf('Manager is undefined for importer %s', get_class($this)));
        }
        return $this->manager;
    }

    /**
     * Return s translator
     * @return null|TranslatorInterface
     */
    public function getTranslator(): ?TranslatorInterface
    {
        return $this->translator;
    }

    /**
     * Sets translator
     * @param TranslatorInterface $translator
     * @return AbstractImporter
     */
    public function setTranslator(TranslatorInterface $translator): ImporterInterface
    {
        $this->translator = $translator;
        return $this;
    }

    /**
     * Returns item manager
     * @return ItemManager|null
     */
    public function getItemManager(): ?ItemManager
    {
        return $this->itemManager;
    }

    /**
     * Sets item manager
     * @param ItemManager $itemManager
     * @return AbstractImporter
     */
    public function setItemManager(ItemManager $itemManager): ImporterInterface
    {
        $this->itemManager = $itemManager;
        return $this;
    }

    /**
     * Translates message
     * @param string $message
     * @param array $parameters
     * @return string
     */
    protected function translate(string $message, array $parameters = [])
    {
        return $this->getTranslator()->trans($message, $parameters, $this->getTranslationsDomain());
    }

    /**
     * {@inheritdoc}
     */
    public function getTitle(): string
    {
        return $this->translate(static::TITLE ?: 'untitled');
    }

    /**
     * {@inheritdoc}
     */
    public function getOptions(): ?ImporterOptionsInterface
    {
        return $this->hasOptions() ? $this->getTask()->getImporterOptions() : null;
    }

    /**
     * {@inheritdoc}
     */
    public function getOptionsArr(): ?array
    {
        return $this->hasOptions() ? $this->getTask()->getImporterOptionsArr() : null;
    }

    /**
     * {@inheritdoc}
     */
    public function getOptionsHash(): ?string
    {
        return $this->hasOptions() ? $this->getTask()->getImporterOptionsHash() : null;
    }

    /**
     * Returns task
     * @return Task|null
     */
    public function getTask(): ?Task
    {
        return $this->task;
    }

    /**
     * Sets task
     * @param Task $task
     * @return AbstractImporter
     */
    public function setTask(Task $task): ImporterInterface
    {
        $this->task = $task;
        return $this;
    }

    /**
     * Returns output
     * @return SymfonyStyle
     */
    public function getOutput(): SymfonyStyle
    {
        if (is_null($this->output)) {
            $this->setOutput(new NullOutput());
        }
        return $this->output;
    }

    /**
     * Sets output
     * @param null|OutputInterface $output
     * @return AbstractImporter
     */
    public function setOutput(?OutputInterface $output): ImporterInterface
    {
        if ($output) {
            if ($output instanceof SymfonyStyle) {
                $this->output = $output;
            } else {
                $this->output = new SymfonyStyle(new ArrayInput([]), $output);
            }
        }
        return $this;
    }

    /**
     * Returns logger
     * @return LoggerInterface
     */
    public function getLogger(): LoggerInterface
    {
        if (is_null($this->logger)) {
            $this->logger = new NullLogger();
        }
        return $this->logger;
    }

    /**
     * Sets logger
     * @param LoggerInterface|null $logger
     * @return ImporterInterface
     */
    public function setLogger(?LoggerInterface $logger): ImporterInterface
    {
        if ($logger) {
            $this->logger = $logger;
        }
        return $this;
    }

    /**
     * Returns imports statistics
     * @return ImportStatistics
     */
    public function getImportStatistics(): ImportStatistics
    {
        if (is_null($this->importStatistics)) {
            $this->importStatistics = new ImportStatistics();
        }
        return $this->importStatistics;
    }

    /**
     * Returns importable fields options
     * @return ImportableFieldsOptions
     */
    public function getImportableFieldsOptions(): ImportableFieldsOptions
    {
        return $this->getOptions()->getImportableFieldsOptions();
    }

    /**
     * Returns importable fields
     * @return ImportableFields
     */
    public function getImportableFields(): ImportableFields
    {
        $fields = new ImportableFields();
        $form = $this->getManager()->getFormFactory()->create($this->getImporterFormClass() ?: $this->getManager()->getCreateModelFormType(), null, $this->getImporterFormOptions());
        $this->addImportableFields($fields, $form);
        return $fields;
    }

    /**
     * Adds importable fields from form
     * @param ImportableFields $fields
     * @param FormInterface $form
     * @param string|null $prefix
     * @param string|null $labelPrefix
     * @return ImportableFields
     */
    protected function addImportableFields(ImportableFields $fields, FormInterface $form, string $prefix = null, string $labelPrefix = null)
    {
        /** @var Form $field */
        foreach ($form as $field) {
            if ($field->getConfig()->getCompound() && $field->getConfig()->getDataClass()) {
                $this->addImportableFields($fields, $field, $prefix.$field->getName().static::COMPOUND_FIELD_NAME_DELIMITER, $this->getTranslator()->trans($field->getConfig()->getOption('label')).' > ');
            } else {
                $fields->add(new ImportableField($prefix.$field->getName(), $labelPrefix.$this->getTranslator()->trans($field->getConfig()->getOption('label'))));
            }
        }
        return $fields;
    }

    /**
     * Returns importable fields
     * @return ImportableFields
     */
    public function getImportableFieldsWithOptions(): ImportableFields
    {
        if (is_null($this->importableFieldsWithOptions)) {
            $this->importableFieldsWithOptions = $this->getImportableFields();
            $this->importableFieldsWithOptions->setFieldsOptions($this->getImportableFieldsOptions());
        }
        return $this->importableFieldsWithOptions;
    }

    /**
     * Adds entry id to processed
     * @param string $entryId
     * @param string $itemId
     * @return $this
     */
    protected function addProcessedEntryId(string $entryId, string $itemId): self
    {
        $this->processedEntriesIds[$entryId] = $itemId;
        return $this;
    }

    /**
     * Checks if entry id is duplicated
     * @param $entryId
     * @return bool
     */
    protected function isDuplicatedEntryId($entryId): bool
    {
        return isset($this->processedEntriesIds[$entryId]);
    }

    /**
     * Checks if item id is duplicated
     * @param string $itemId
     * @return bool
     */
    protected function isDuplicatedItemId(string $itemId): bool
    {
        return in_array($itemId, $this->processedEntriesIds);
    }

    /**
     * Creates new object
     * @param Item $importedItem
     * @param ImportFormData $importFormData
     * @return SimpleModelInterface
     */
    public function findOrCreateObject(Item $importedItem, ImportFormData $importFormData): SimpleModelInterface
    {
        return $importedItem->getTarget()->getModel() ?: $this->getManager()->new();
    }

    /**
     * Returns import form data
     * @param Item $importedItem
     * @return ImportFormData
     */
    public function getImportFormData(Item $importedItem): ImportFormData
    {
        $importFormData = [] ;
        foreach ($this->getImportableFieldsWithOptions() as $field) {
            $path = explode(static::COMPOUND_FIELD_NAME_DELIMITER, $field->getName());
            $lastName = array_pop($path);
            $current = &$importFormData;
            foreach ($path as $name) {
                if (!isset($current[$name]) || !is_array($current[$name])) {
                    $current[$name] = [];
                }
                $current = &$current[$name];
            }
            $current[$lastName] = $this->getFieldImportedValue($field, $importedItem->getData());
        }
        return new ImportFormData($importFormData);
    }

    /**
     * Returns data from importFormData
     * @param ImportFormData $importFormData
     * @param Item $importedItem
     * @return array
     */
    public function getDataFromImportFormData(ImportFormData $importFormData, Item $importedItem): array
    {
        $flattenFormData = function (array $formDataArr, string $prefix = '') use (&$flattenFormData) {
            $formData = [];
            foreach($formDataArr as $key => $value) {
                if (is_array($value)) {
                    $formData = array_merge($formData, $flattenFormData($value, $key.static::COMPOUND_FIELD_NAME_DELIMITER));
                } else {
                    $formData[$prefix.$key] = $value;
                }
            }
            return $formData;
        };

        $formData = $flattenFormData($importFormData->getData());

        $data = $importedItem->getData() ;
        /** @var ImportableField $field */
        foreach ($this->getImportableFieldsWithOptions() as $field) {
            if (array_key_exists($field->getName(), $formData)) {
                $data[$field->getOptions()->getKey()] = $formData[$field->getName()];
            }
        }
        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function importTask(Task $task, array $options = []): ImporterInterface
    {
        if (isset($options['page'])) {
            $predefinedPages = explode(',', (string) $options['page']);
            foreach ($predefinedPages as $page) {
                $this->importTaskPage($task, $page, $options);
            }
        } else {
            $page = $task->getFirstPageValue();
            do {
                $result = $this->importTaskPage($task, $page, $options);
                $page++;
            } while ($task->isPaginatedLink() && $result);
        }

        return $this;
    }

    /**
     * Imports data for one page
     * @param $task
     * @param int $page
     * @param array $options
     * @return bool
     */
    protected function importTaskPage($task, int $page, array $options = []): bool
    {
        $link = $task->getPreparedLink($page);
        $this->getOutput()->section(sprintf('Reading data from resource %s', $task->isPaginatedLink() ? $page : ''));
        $this->getLogger()->info(sprintf('Reading data from resource %s: START', $link));

        $data = $task->getDataFromLink(false, $link);

        $this->getOutput()->text('Done');
        $this->getLogger()->info(sprintf('Reading data from resource %s: END', $link));

        if ($task->isPaginatedLink() && empty($data->getExampleData())) {
            return false;
        }
        $this->importData($data);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function importItem(Item $item): ImporterInterface
    {
        $this->importFromItem($item, true);
        return $this;
    }

    /**
     * Some actions before import data
     */
    protected function beforeImportData()
    {

    }

    /**
     * Some actions after import data
     */
    protected function afterImportData()
    {

    }

    /**
     * {@inheritdoc}
     */
    public function importData(ImportedData $data): ImporterInterface
    {
//        $this->getOutput()->text('Memory1: '.number_format(memory_get_usage()/1000, 2).'kb');

        $this->getOutput()->section('Processing data');
        $this->getLogger()->info('Processing data: START');

        if (empty($data->getExampleData())) {
            throw new ImporterException("There are no data from source, nothing to sync.");
        }

        $this->beforeImportData();

        $items = ($data->isDataInRoot() || !$this->getOptions()->getDataPath()) ? $data->getData() : $this->getEntryFieldValue((array) $data->getData(), $this->getOptions()->getDataPath());
        $itemsCount = count($items);

        $this->getOutput()->text('Total entries: '.$itemsCount);
        $this->getLogger()->info('Processing data: END', ['total_entries' => $itemsCount]);

        $butchLength = $this->getButchLength();

        $this->getOutput()->section('Importing');
        $this->getLogger()->info('Importing: START');

        $progressBar = $this->getOutput()->createProgressBar($itemsCount);
        $progressBar->setFormat('debug');
        $progressBar->start();
        $this->getImportStatistics()->reset()->setAll($itemsCount);

        foreach ($items as $row) {
            $progressBar->advance();
            if (empty($row)) {
                $this->getImportStatistics()->incrementSkipped();
                $this->logImportItemResult('info', 'SKIPPED', null, (array) $row, ['No data in row']);
                continue;
            }
            $item = $data->prepareRow($row);
            $entryId = $this->getEntryId($item);
            if (!$this->isDuplicatedEntryId($entryId)) {
                try {
                    $this->importEntryItem($entryId, $item);
                } catch (\Exception $e) {
                    $this->getLogger()->error($e->getMessage(), ['importFormData' => $item]);
                    $this->getImportStatistics()->incrementErrors();
                }
            } else {
                try {
                    $this->importDuplicatedEntryItem($entryId, $item);
                    $this->getImportStatistics()->incrementDuplicates();
                    $this->logImportItemResult('info', 'DUPLICATE', null, $item);
                } catch (\Exception $e) {
                    $this->getLogger()->error($e->getMessage(), ['importFormData' => $item]);
                    $this->getImportStatistics()->incrementErrors();
                }
            }

            if ($progressBar->getProgress() % $butchLength == 0 && $this->getManager() instanceof AbstractDoctrineManager) {
                $this->getManager()->getManager()->flush();
                $this->getManager()->getManager()->clear();
            }
            unset($row);
            unset($item);
            unset($entryId);
        }

        if ($this->getManager() instanceof AbstractDoctrineManager) {
            $this->getManager()->getManager()->flush();
            $this->getManager()->getManager()->clear();
        }

        $progressBar->finish();
        $this->getOutput()->newLine(2);
        $this->afterImportData();

        $this->getOutput()->table(
            ['All', 'Skipped', 'Scheduled', 'Created', 'Updated', 'Duplicates', 'Errors'/*, 'Deleted'*/],
            [
                [
                    $this->getImportStatistics()->getAll(),
                    $this->getImportStatistics()->getSkipped(),
                    $this->getImportStatistics()->getScheduled(),
                    $this->getImportStatistics()->getCreated(),
                    $this->getImportStatistics()->getUpdated(),
                    $this->getImportStatistics()->getDuplicates(),
                    $this->getImportStatistics()->getErrors(),
//                    $this->getImportStatistics()->getDeleted(),
                ],
            ]
        );

        $this->getLogger()->info('Importing: END', [
            'all'       => $this->getImportStatistics()->getAll(),
            'skipped'   => $this->getImportStatistics()->getSkipped(),
            'scheduled' => $this->getImportStatistics()->getScheduled(),
            'created'   => $this->getImportStatistics()->getCreated(),
            'updated'   => $this->getImportStatistics()->getUpdated(),
            'duplicates'=> $this->getImportStatistics()->getDuplicates(),
            'errors'    => $this->getImportStatistics()->getErrors(),
//            'deleted' => $this->getImportStatistics()->getDeleted(),
        ]);

        return $this;
    }

    /**
     * Checks if import of item needed
     * @param Item $importedItem
     * @param array $item
     * @return bool
     */
    protected function isProcessOfImportItemNeeded(Item $importedItem, array $item): bool
    {
        return $this->getOptions()->isForce()
            || $importedItem->getDataHash() !== $importedItem->generateDataHash($item)
            || $importedItem->getConfigHash() !== $this->getOptionsHash()
            || !$importedItem->getTarget()->getModel()
            || !$importedItem->getTarget()->getModel()->getId()
        ;
    }

    /**
     * Imports data for one object
     * @param string $entryId
     * @param array $item
     */
    public function importEntryItem(string $entryId, array $item): void
    {
        $importedItem = $this->getImportedItem($entryId, $item);
        if ($this->isProcessOfImportItemNeeded($importedItem, $item)) {
            $importedItem
                ->setData($item)
                ->setConfigHash($this->getOptionsHash())
            ;
            $this->importFromItem($importedItem);
            $this->saveImportedItem($importedItem);
        } else {
            $importedItem
                ->setStatusId(Item::STATUS_SKIPPED)
            ;
            $this->saveImportedItem($importedItem);
            $this->getImportStatistics()->incrementSkipped();
            $this->logImportItemResult('info', 'SKIPPED', $importedItem->getTarget()->getModel(), $this->getImportFormData($importedItem));
        }
        $this->addProcessedEntryId($entryId, $importedItem->getId());
        unset($importedItem);
    }

    public function configureGetFormOptions(OptionsResolver $resolver): OptionsResolver
    {
        $resolver->setDefaults([
            'method' => Request::METHOD_POST,
            'model' => null,
        ]);

        return $resolver;
    }

    /**
     * Returns form
     * @param array $options
     * @return FormInterface
     */
    public function getForm(array $options = []): FormInterface
    {
        $options = $this->configureGetFormOptions(new OptionsResolver())->resolve($options);

        return $this->getManager()->getCreateModelForm([
            'formClass' => $this->getImporterFormClass(),
            'formOptions' => $this->getImporterFormOptions(),
            'requestMethod' => $options['method'],
            'model' => $options['model'],
        ]);
    }

    /**
     * Checks if direct submit clear missing needed
     * @param SimpleModelInterface $object
     * @param Item $importedItem
     * @param ImportFormData $importFormData
     * @return bool
     */
    protected function isDirectSubmitClearMissing(SimpleModelInterface $object, Item $importedItem, ImportFormData $importFormData): bool
    {
        return $object->isModelNew() || $this->getOptions()->isSyncData() || !$importFormData->isMethodPatch();
    }

    /**
     * Checks object existence
     * @param SimpleModelInterface $object
     * @return bool
     */
    protected function checkObjectExistence(SimpleModelInterface $object): bool
    {
        if (!$object->isModelNew() && $this->getOptions()->isSkipExisted()) {
            throw new SkippedItemException(sprintf('Skip updating of %s with ID: %s', $object->getModelTitle(), $object->getId()));
        }

        return true;
    }

    /**
     * Imports data from Item
     * @param Item $importedItem
     * @param bool $flush
     */
    protected function importFromItem(Item $importedItem, bool $flush = false): void
    {
        $importFormData = $this->getImportFormData($importedItem);
        try {
            $object = $importedItem->getTarget()->getModel();
        } catch (InvalidTargetException $e) {
            $object = null;
        }

        try {
            $isNew = false;
            if (!$object || !$object->getId()) {
                $object = $this->findOrCreateObject($importedItem, $importFormData);
                $isNew = !(bool) $object->getId();
            }
            if (!$isNew){
                $importFormData->setMethodPatch();
            }
            $importedItem->setTarget($object);

            $this->checkObjectExistence($object);
            $this->beforeObjectUpdate($object, $importedItem, $importFormData);

            $form = $this->getForm(['model' => $object]);
            $this->getManager()
                ->processModelForm($form, [
                    'directSubmit' => true,
                    'data' => $importFormData->getData(),
                    'directSubmitClearMissing' => $this->isDirectSubmitClearMissing($object, $importedItem, $importFormData),
                ])
                ->checkModelForm($form)
                ->save($object, [
                    //                'validate' => true,
                    'flush' => false,
                ])
            ;
            unset($form);
            $this->afterObjectUpdate($object, $importedItem, $importFormData);
            if ($isNew) {
                $importedItem
                    ->setStatusId(Item::STATUS_CREATED)
                ;
                $this->getImportStatistics()->incrementCreated();
                $this->logImportItemResult('info', 'CREATED', $object, $importFormData);
            } else {
                $importedItem
                    ->setStatusId(Item::STATUS_UPDATED)
                ;
                $this->getImportStatistics()->incrementUpdated();
                $this->logImportItemResult('info', 'UPDATED', $object, $importFormData);
            }
        } catch (SkippedItemException $e) {
            $this->getImportStatistics()->incrementSkipped();
            $this->logImportItemResult('info', 'SKIPPED: '.$e->getMessage(), $object, $importFormData);
            $importedItem
                ->setStatusId(Item::STATUS_SKIPPED)
            ;
        } catch (FormErrorsException $e) {
            $this->getImportStatistics()->incrementErrors();
            $errors = $e->getFormErrors();
            $this->logImportItemResult('error', $e->getMessage(), $object, $importFormData, $errors);
            $importedItem
                ->setStatusId(Item::STATUS_DATA_ERROR)
                ->setErrors($errors)
            ;
        } catch (\Exception $e) {
            $this->getImportStatistics()->incrementErrors();
            $this->logImportItemResult('error', $e->getMessage(), $object, $importFormData);
            $importedItem
                ->setStatusId(Item::STATUS_ERROR)
                ->setErrors([$e->getMessage()])
            ;
            if (!$importedItem->getTarget() || !$importedItem->getTarget()->getClassName()) {
                $importedItem->setTarget($this->getManager()->new());
            }
        }
    }

    /**
     * Imports data for one object
     * @param string $entryId
     * @param array $item
     */
    public function importDuplicatedEntryItem(string $entryId, array $item): void
    {
        $itemId = $this->generateItemId($entryId, $item);
        if (!$this->isDuplicatedItemId($itemId) && !$this->getItemManager()->get($itemId))
        {
            $importedItem = $this->getNewImportedItem($entryId, $item);
            $importedItem
                ->setStatusId(Item::STATUS_DUPLICATE)
                ->setConfigHash($this->getOptionsHash())
                ->setTarget($this->getManager()->new())
            ;
            $this->saveImportedItem($importedItem);
        }
    }

    /**
     * Some actions before update
     * @param SimpleModelInterface $object
     * @param Item $importedItem
     * @param ImportFormData $importFormData
     */
    protected function beforeObjectUpdate(SimpleModelInterface $object, Item $importedItem, ImportFormData $importFormData): void
    {

    }

    /**
     * Some actions after update
     * @param SimpleModelInterface $object
     * @param Item $importedItem
     * @param ImportFormData $importFormData
     */
    protected function afterObjectUpdate(SimpleModelInterface $object, Item $importedItem, ImportFormData $importFormData): void
    {

    }

    /**
     * Write import status to log
     * @param string $method
     * @param string $message
     * @param SimpleModelInterface $object
     * @param ImportFormData $importFormData
     * @param array $errors
     */
    protected function logImportItemResult(string $method, string $message, ?SimpleModelInterface $object, $importFormData, array $errors = []): void
    {
        $this->getLogger()->$method($message, [
            'objectId' => $object ? ($object->getId() ?: 'NEW') : null,
            'objectClass' => $object ? get_class($object) : null,
            'errors' => $errors,
            'importFormData' => $importFormData instanceof ImportFormData ? $importFormData->getData() : (array) $importFormData,
        ]);
    }

    /**
     * Returns imported item
     * @param string $entryId
     * @param array|null $data
     * @return Item
     */
    public function getImportedItem(string $entryId, ?array $data): Item
    {
        return $this->getItemManager()->getImportedItem($this->getTask()->getId(), $entryId, $this->generateItemId($entryId, $data));
    }

    /**
     * Returns imported item
     * @param string $entryId
     * @param array|null $data
     * @return Item
     */
    public function getNewImportedItem(string $entryId, ?array $data): Item
    {
        return $this->getItemManager()
            ->getNewImportedItem($this->getTask()->getId(), $entryId)
            ->setData($data)
        ;
    }

    /**
     * Generates item id
     * @param string $entryId
     * @param array|null $data
     * @return string
     */
    public function generateItemId(string $entryId, ?array $data): string
    {
        return Item::generateId($this->getTask()->getId(), $entryId, Item::generateDataHash($data));
    }

    /**
     * Saves imported item
     * @param Item $importedItem
     * @param bool $flush
     * @return AbstractImporter
     */
    public function saveImportedItem(Item $importedItem, bool $flush = false): self
    {
        $this->getItemManager()->save($importedItem, ['flush' => $flush]);
        return $this;
    }

    /**
     * Returns items with unique itemID
     * @param array $data
     * @return array
     */
    protected function getUniqueItems(array $data): array
    {
        $unique = [];
        foreach ($data as $item) {
            $itemId = $this->getEntryId($item);
            $unique[$itemId] = $item;
        }

//        ksort($unique);

        return $unique;
    }

    /**
     * Returns item (entry) fields for hash ID
     * @return string|array
     */
    public function getItemHashIdFields()
    {
        return $this->getOptions()->getItemHashIdFields();
    }

    /**
     * Returns ID of item (entry)
     * @param array $item
     * @return string
     */
    protected function getEntryId(array $item): string
    {
        $hashParams = [];
        foreach ($this->getItemHashIdFields() as $field) {
            $value = $this->getEntryFieldValue($item, $field);
            if (!is_scalar($value)) {
                $value = json_encode($value);
            }
            array_push($hashParams, $value);
        }

        return $this->makeHash($hashParams);
    }

    /**
     * Returns hash
     * @param array $hashParams
     * @return string
     */
    protected function makeHash(array $hashParams): string
    {
        return md5(join('-', (array) $hashParams));
    }

    /**
     * Returns path delimiter
     * @return string
     */
    protected function getPathDelimiter(): string
    {
        return $this->getOptions()->getPathDelimiter();
    }

    /**
     * Returns value for item (entry) field or path
     * @param array $item
     * @param null|string $field
     * @return array|mixed|null
     */
    protected function getEntryFieldValue(array $item, ?string $field)
    {
        if (is_null($field) || $field === '') {
            return null;
        }
        $value = $item;
        foreach (explode($this->getPathDelimiter(), $field) as $key) {
            if (!isset($value[$key])) {
                return null;
            }
            $value = $value[$key];
        }

        return $value;
    }

    /**
     * Returns imported value from imported item (entry)
     * @param ImportableField $field
     * @param array $data
     * @return array|mixed|null|string
     */
    protected function getFieldImportedValue(ImportableField $field, array $data)
    {
        if (is_null($field->getOptions()->getKey()) || !strlen($field->getOptions()->getKey())) {
            $value = isset($data[$field->getName()]) ? $data[$field->getName()] : null;
        } else {
            $value = $this->getEntryFieldValue($data, $field->getOptions()->getKey());

            // Check fallbackKey
            if (empty($value) && !empty($field->getOptions()->getKey())) {
                $fallbackKeys = (array) $field->getOptions()->getKey();

                foreach ($fallbackKeys as $key) {
                    $value = $this->getEntryFieldValue($data, $key);

                    if (!empty($value)) {
                        break;
                    }
                }
            }
        }

        // Apply defaultValue
        if (empty($value) && !empty($field->getOptions()->getDefaultValue())) {
            $value = $field->getOptions()->getDefaultValue();
        }

        return is_string($value) ? trim($value) : $value;
    }
}