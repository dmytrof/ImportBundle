<?php

namespace Dmytrof\ImportBundle\Tests\Unit\Model;

use Dmytrof\ImportBundle\Exception\{ImportableFieldsOptionsException, InvalidArgumentException};
use Dmytrof\ImportBundle\Model\{ImportableField, ImportableFieldOptions, ImportableFields, ImportableFieldsOptions};
use PHPUnit\Framework\TestCase;

class ImportableFieldsTest extends TestCase
{
    public function testFieldDefaults(): void
    {
        $field = new ImportableField('title');

        $this->assertSame('title', $field->getName());
        $this->assertNull($field->getLabel());
        $this->assertSame(100, $field->getPosition());
        $this->assertInstanceOf(ImportableFieldOptions::class, $field->getOptions());
    }

    public function testFieldsAreKeyedByName(): void
    {
        $fields = new ImportableFields([new ImportableField('title'), new ImportableField('price')]);
        $fields->set('ignored-key', new ImportableField('sku'));

        $this->assertSame(['title', 'price', 'sku'], $fields->getKeys());
    }

    public function testOnlyImportableFieldsAccepted(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new ImportableFields())->add('title');
    }

    public function testSortPositions(): void
    {
        $fields = (new ImportableFields([
            new ImportableField('c', null, 30),
            new ImportableField('a', null, 10),
            new ImportableField('b', null, 20),
        ]))->sortPositions();

        $this->assertSame(['a', 'b', 'c'], $fields->getKeys());
    }

    public function testFieldsOptions(): void
    {
        $fields = new ImportableFields([new ImportableField('title'), new ImportableField('price')]);
        $fieldsOptions = new ImportableFieldsOptions([
            'title' => (new ImportableFieldOptions())->setKey('name'),
            'unknown' => (new ImportableFieldOptions())->setKey('x'),
        ]);

        $fields->setFieldsOptions($fieldsOptions);

        $this->assertSame('name', $fields->get('title')->getOptions()->getKey());
        $this->assertNull($fields->get('price')->getOptions()->getKey());
        $this->assertSame(['title', 'price'], array_keys($fields->getFieldsOptions()));
    }

    public function testFieldsOptionsCollectionValidation(): void
    {
        $options = new ImportableFieldsOptions();
        $options->set('title', null);
        $this->assertFalse($options->containsKey('title'), 'null values are ignored');

        $this->expectException(InvalidArgumentException::class);
        $options->set('title', 'not options');
    }

    public function testFieldsOptionsCannotBeAdded(): void
    {
        $this->expectException(ImportableFieldsOptionsException::class);
        (new ImportableFieldsOptions())->add(new ImportableFieldOptions());
    }

    public function testFieldsOptionsMagicAccess(): void
    {
        $options = new ImportableFieldsOptions();
        $options->title = (new ImportableFieldOptions())->setKey('name');

        $this->assertSame('name', $options->title->getKey());
    }

    public function testFieldOptionsChangesPropagateToObservers(): void
    {
        $observer = new class implements \SplObserver {
            public int $calls = 0;
            public function update(\SplSubject $subject): void { $this->calls++; }
        };
        $fieldOptions = new ImportableFieldOptions();
        $options = new ImportableFieldsOptions(['title' => $fieldOptions]);
        $options->attach($observer);

        $fieldOptions->setKey('name');
        $fieldOptions->setFallbackKey('title');
        $fieldOptions->setDefaultValue('Untitled');
        $this->assertSame(3, $observer->calls);

        $options->detach($observer);
        $fieldOptions->setKey('other');
        $this->assertSame(3, $observer->calls);
    }
}
