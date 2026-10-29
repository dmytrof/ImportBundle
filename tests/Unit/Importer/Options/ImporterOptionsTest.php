<?php

namespace Dmytrof\ImportBundle\Tests\Unit\Importer\Options;

use Dmytrof\ImportBundle\Importer\Options\ImporterOptions;
use Dmytrof\ImportBundle\Model\{ImportableField, ImportableFieldOptions, ImportableFields};
use PHPUnit\Framework\TestCase;

class ImporterOptionsTest extends TestCase
{
    public function testDelimiters(): void
    {
        $this->assertSame('/', ImporterOptions::getPathDelimiter());
        $this->assertSame(',', ImporterOptions::getIdFieldsDelimiter());
    }

    public function testItemHashIdFieldsString(): void
    {
        $options = (new ImporterOptions())->setItemHashIdFieldsStr('sku,lang');

        $this->assertSame(['sku', 'lang'], $options->getItemHashIdFields());
        $this->assertSame('sku,lang', $options->getItemHashIdFieldsStr());
        $this->assertSame('', (new ImporterOptions())->getItemHashIdFieldsStr());
    }

    public function testFlags(): void
    {
        $options = new ImporterOptions();
        $this->assertFalse($options->isForce());
        $this->assertFalse($options->isSyncData());
        $this->assertFalse($options->isSkipExisted());

        $options->setForce(true)->setSyncData(true)->setSkipExisted(true);
        $this->assertTrue($options->isForce());
        $this->assertTrue($options->isSyncData());
        $this->assertTrue($options->isSkipExisted());
    }

    public function testPrepareImportableFieldsOptionsKeepsExistingOptions(): void
    {
        $options = new ImporterOptions();
        $options->getImportableFieldsOptions()->set('title', (new ImportableFieldOptions())->setKey('name'));

        $options->prepareImportableFieldsOptions(new ImportableFields([
            new ImportableField('title'),
            new ImportableField('price'),
        ]));

        $fieldsOptions = $options->getImportableFieldsOptions();
        $this->assertSame(['title', 'price'], $fieldsOptions->getKeys());
        $this->assertSame('name', $fieldsOptions->get('title')->getKey());
        $this->assertNull($fieldsOptions->get('price')->getKey());
    }

    public function testArrayRoundTrip(): void
    {
        $options = (new ImporterOptions())
            ->setDataPath('data/items')
            ->setItemHashIdFields(['sku'])
            ->setSyncData(true)
        ;
        $options->getImportableFieldsOptions()->set('title', (new ImportableFieldOptions())->setKey('name')->setDefaultValue('N/A'));

        $array = $options->toArray();
        $this->assertArrayNotHasKey('observers', $array);
        $this->assertSame(['key' => 'name', 'defaultValue' => 'N/A'], $array['importableFieldsOptions']['title'], 'null values are omitted');

        $restored = (new ImporterOptions())->fromArray($array);
        $this->assertSame('data/items', $restored->getDataPath());
        $this->assertSame(['sku'], $restored->getItemHashIdFields());
        $this->assertTrue($restored->isSyncData());
        $this->assertSame('N/A', $restored->getImportableFieldsOptions()->get('title')->getDefaultValue());
        $this->assertSame($array, $restored->toArray());
    }

    public function testObserversAreNotified(): void
    {
        $observer = new class implements \SplObserver {
            public array $subjects = [];
            public function update(\SplSubject $subject): void { $this->subjects[] = $subject; }
        };
        $options = new ImporterOptions();
        $options->attach($observer);

        $options->setDataPath('a');
        $options->getImportableFieldsOptions()->set('title', new ImportableFieldOptions());
        $options->getImportableFieldsOptions()->get('title')->setKey('name');

        $this->assertCount(2, $observer->subjects);
        $this->assertContainsOnlyInstancesOf(ImporterOptions::class, $observer->subjects);
    }
}
