<?php

/*
 * This file is part of the DmytrofImportBundle package.
 *
 * (c) Dmytro Feshchenko <dmytro.feshchenko@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dmytrof\ImportBundle\Service;

use Doctrine\Common\Collections\{
	Collection, ArrayCollection
};

use Dmytrof\ImportBundle\{Exception\ImporterException, Importer\ImporterInterface, Manager\ItemManager};
use Symfony\Component\Translation\TranslatorInterface;

class ImportersContainer implements \IteratorAggregate
{
    /**
     * @var Collection|ImporterInterface[]
     */
	protected $importers;

    /**
     * @var TranslatorInterface
     */
	protected $translator;

    /**
     * @var ItemManager
     */
	protected $itemManager;

    /**
     * ImportersContainer constructor.
     * @param TranslatorInterface $translator
     * @param ItemManager $itemManager
     * @param iterable $importers
     */
    public function __construct(TranslatorInterface $translator, ItemManager $itemManager, iterable $importers)
    {
        $this->translator = $translator;
        $this->itemManager = $itemManager;
        $this->importers = new ArrayCollection();
        foreach ($importers as $importer) {
            $this->addImporter($importer);
        }
    }

    /**
     * @return TranslatorInterface
     */
    protected function getTranslator(): TranslatorInterface
    {
        return $this->translator;
    }

    /**
     * @return ItemManager
     */
    protected function getItemManager(): ItemManager
    {
        return $this->itemManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        return $this->importers->getIterator();
    }

    /**
     * Adds importer
     * @param ImporterInterface $importer
     * @return ImportersContainer
     */
    public function addImporter(ImporterInterface $importer): self
    {
        if (!$importer->getTranslator()) {
            $importer->setTranslator($this->getTranslator());
        }
        if (!$importer->getItemManager()) {
            $importer->setItemManager($this->getItemManager());
        }
        $this->importers->set($importer->getCode(), $importer);
        return $this;
    }

    /**
     * Returns importer by code
     * @param string $code
     * @return ImporterInterface
     */
    public function get(string $code): ImporterInterface
    {
        if (!$this->has($code)) {
            throw new ImporterException(sprintf('Undefined importer with code %s', $code));
        }
        return $this->importers->get($code);
    }

    /**
     * Checks if container has importer with code
     * @param string $code
     * @return bool
     */
    public function has(string $code): bool
    {
        return $this->importers->containsKey($code);
    }

    /**
     * Returns array of importers titles
     * @return array
     */
    public function getImportersTitles(): array
    {
        $titles = [];
        foreach ($this->importers as $importer) {
            $titles[$importer->getCode()] = $importer->getTitle();
        }
        return $titles;
    }

    /**
     * Returns array of importers codes
     * @return array
     */
    public function getImportersCodes(): array
    {
        return $this->importers->getKeys();
    }
}