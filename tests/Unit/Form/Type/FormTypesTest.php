<?php

namespace Dmytrof\ImportBundle\Tests\Unit\Form\Type;

use Dmytrof\ImportBundle\Form\Type\DataType\{BooleanType, DateTimeType, FloatType, IntegerType, StringType};
use Dmytrof\ImportBundle\Form\Type\{ImporterType, ImportItemStatusType, PeriodType, ReaderType};
use Dmytrof\ImportBundle\Form\Type\Importer\{ImportableFieldsOptionsType, ImporterOptionsType};
use Dmytrof\ImportBundle\Form\Type\Reader\CsvReaderOptionsType;
use Dmytrof\ImportBundle\Importer\Options\ImporterOptions;
use Dmytrof\ImportBundle\Model\{ImportableField, ImportableFields, Item, Task};
use Dmytrof\ImportBundle\Reader\Options\CsvReaderOptions;
use Dmytrof\ImportBundle\Tests\Fixtures\{CreatesTasksTrait, TestImporter};
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

// TypeTestCase mocks the event dispatcher without expectations (PHPUnit 12+ notice)
#[AllowMockObjectsWithoutExpectations]
class FormTypesTest extends TypeTestCase
{
    use CreatesTasksTrait;

    protected function getExtensions(): array
    {
        return [new PreloadedExtension([
            new ImporterType($this->createImportersContainer()),
            new ReaderType($this->createReadersContainer()),
        ], [])];
    }

    public function testPeriodType(): void
    {
        $form = $this->factory->create(PeriodType::class);
        $form->submit((string) Task::PERIOD_EVERY_4_HOURS);

        $this->assertTrue($form->isSynchronized());
        $this->assertSame(Task::PERIOD_EVERY_4_HOURS, $form->getData());
    }

    public function testImportItemStatusType(): void
    {
        $form = $this->factory->create(ImportItemStatusType::class);
        $form->submit((string) Item::STATUS_CREATED);

        $this->assertSame(Item::STATUS_CREATED, $form->getData());
    }

    public function testImporterAndReaderTypes(): void
    {
        $importer = $this->factory->create(ImporterType::class);
        $importer->submit(TestImporter::CODE);
        $this->assertSame(TestImporter::CODE, $importer->getData());

        $reader = $this->factory->create(ReaderType::class);
        $this->assertSame(['json', 'xml', 'rss', 'csv'], array_map(fn ($c) => $c->value, $reader->createView()->vars['choices']));
        $reader->submit('csv');
        $this->assertSame('csv', $reader->getData());
    }

    public function testBooleanType(): void
    {
        foreach (['0' => false, '1' => true, 'yes' => true] as $submitted => $expected) {
            $form = $this->factory->create(BooleanType::class);
            $form->submit((string) $submitted);
            $this->assertSame($expected, $form->getData(), "Submitted: $submitted");
        }
        $form = $this->factory->create(BooleanType::class);
        $form->submit(null);
        $this->assertFalse($form->getData());
    }

    public function testDateTimeType(): void
    {
        $form = $this->factory->create(DateTimeType::class);
        $form->submit('2026-10-28T12:00:00+00:00');

        $this->assertInstanceOf(\DateTime::class, $form->getData());
        $this->assertSame(strtotime('2026-10-28T12:00:00+00:00'), $form->getData()->getTimestamp());
    }

    public function testScalarTypes(): void
    {
        $string = $this->factory->create(StringType::class);
        $string->submit('Apple');
        $this->assertSame('Apple', $string->getData());

        $int = $this->factory->create(IntegerType::class);
        $int->submit('42');
        $this->assertSame(42, $int->getData());

        $float = $this->factory->create(FloatType::class);
        $float->submit('1.5');
        $this->assertSame(1.5, $float->getData());
    }

    public function testCsvReaderOptionsType(): void
    {
        $options = new CsvReaderOptions();
        $form = $this->factory->create(CsvReaderOptionsType::class, $options);
        $form->submit(['hasHeadingRow' => '1', 'delimiter' => ';', 'enclosure' => "'", 'escape' => '\\']);

        $this->assertTrue($form->isValid());
        $this->assertTrue($options->getHasHeadingRow());
        $this->assertSame(';', $options->getDelimiter());
        $this->assertSame("'", $options->getEnclosure());
    }

    public function testImporterOptionsType(): void
    {
        $fields = new ImportableFields([new ImportableField('title', 'Title'), new ImportableField('price', 'Price')]);
        $options = (new ImporterOptions())->prepareImportableFieldsOptions($fields);

        $form = $this->factory->create(ImporterOptionsType::class, $options, ['importable_fields' => $fields]);
        $this->assertSame(['title', 'price'], array_keys($form->get('importableFieldsOptions')->all()));

        $form->submit([
            'dataPath' => 'items',
            'itemHashIdFieldsStr' => 'sku,lang',
            'importableFieldsOptions' => [
                'title' => ['key' => 'name', 'fallbackKey' => 'title_en', 'defaultValue' => 'Untitled'],
                'price' => ['key' => 'cost'],
            ],
        ]);

        $this->assertTrue($form->isSynchronized());
        $this->assertSame('items', $options->getDataPath());
        $this->assertSame(['sku', 'lang'], $options->getItemHashIdFields());
        $title = $options->getImportableFieldsOptions()->get('title');
        $this->assertSame(['name', 'title_en', 'Untitled'], [$title->getKey(), $title->getFallbackKey(), $title->getDefaultValue()]);
        $this->assertSame('cost', $options->getImportableFieldsOptions()->get('price')->getKey());
    }

    public function testImportableFieldsOptionsTypeRequiresFields(): void
    {
        $this->expectException(\Symfony\Component\OptionsResolver\Exception\MissingOptionsException::class);
        $this->factory->create(ImportableFieldsOptionsType::class);
    }
}
