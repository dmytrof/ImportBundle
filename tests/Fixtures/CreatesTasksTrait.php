<?php

namespace Dmytrof\ImportBundle\Tests\Fixtures;

use Dmytrof\ImportBundle\Manager\ItemManager;
use Dmytrof\ImportBundle\Model\Task;
use Dmytrof\ImportBundle\Reader\ReaderInterface;
use Dmytrof\ImportBundle\Service\{ImportersContainer, ReadersContainer};
use Dmytrof\ImportBundle\Tests\Fixtures\Reader\{MockedCsvReader, MockedJsonReader, MockedRssReader, MockedXmlReader};

/**
 * Builds a Task wired with an importers container (TestImporter) and readers with mocked HTTP.
 * Use inside a PHPUnit TestCase.
 */
trait CreatesTasksTrait
{
    protected ?ImportersContainer $importersContainer = null;
    protected ?ReadersContainer $readersContainer = null;

    protected function createImportersContainer(): ImportersContainer
    {
        return $this->importersContainer ??= new ImportersContainer(
            new IdentityTranslator(),
            $this->createStub(ItemManager::class),
            [new TestImporter()]
        );
    }

    /**
     * @param ReaderInterface[] $readers
     */
    protected function createReadersContainer(array $readers = []): ReadersContainer
    {
        $translator = new IdentityTranslator();

        return $this->readersContainer ??= new ReadersContainer($readers ?: [
            new MockedJsonReader($translator),
            new MockedXmlReader($translator),
            new MockedRssReader($translator),
            new MockedCsvReader($translator),
        ]);
    }

    protected function createTask(): Task
    {
        return (new Task())
            ->setImportersContainer($this->createImportersContainer())
            ->setReadersContainer($this->createReadersContainer())
        ;
    }
}
