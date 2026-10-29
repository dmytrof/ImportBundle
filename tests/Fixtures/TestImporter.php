<?php

namespace Dmytrof\ImportBundle\Tests\Fixtures;

use Dmytrof\ImportBundle\Importer\AbstractImporter;
use Dmytrof\ImportBundle\Model\{ImportableField, ImportableFields, ImportFormData, ImportedData};

/**
 * Importer with a fixed set of importable fields, so it can be used without a form factory / manager.
 */
class TestImporter extends AbstractImporter
{
    public const CODE = 'test';
    public const TITLE = 'Test importer';

    public function getImportableFields(): ImportableFields
    {
        return new ImportableFields([
            new ImportableField('title', 'Title'),
            new ImportableField('price', 'Price'),
            new ImportableField('address.city', 'Address > City'),
        ]);
    }

    /**
     * Exposes protected entry id calculation
     */
    public function entryId(array $item): string
    {
        return $this->getEntryId($item);
    }
}
