<?php

namespace Dmytrof\ImportBundle\Tests\Unit\Validation;

use Dmytrof\ImportBundle\Model\{Item, Task};
use Dmytrof\ImportBundle\Reader\Options\CsvReaderOptions;
use Dmytrof\ImportBundle\Tests\Fixtures\{CreatesTasksTrait, TestImporter};
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ValidationTest extends TestCase
{
    use CreatesTasksTrait;

    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->validator = Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator();
    }

    private function validTask(): Task
    {
        $task = $this->createTask()
            ->setTitle('Products feed')
            ->setLink('https://example.com/feed.json')
            ->setImporterCode(TestImporter::CODE)
            ->setReaderCode('json')
            ->setPeriod(Task::PERIOD_EVERY_HOUR)
        ;
        $task->getImporterOptions()->setItemHashIdFields(['sku']);

        return $task;
    }

    private function violations(object $object, ?array $groups = null): array
    {
        $result = [];
        foreach ($this->validator->validate($object, null, $groups) as $violation) {
            $result[$violation->getPropertyPath()][] = $violation->getMessage();
        }

        return $result;
    }

    public function testValidTask(): void
    {
        $this->assertSame([], $this->violations($this->validTask(), ['Default', Task::VALIDATION_GROUP_LINK_AND_PARSER]));
    }

    /**
     * Regression: title used to be validated with LessThanOrEqual(250), which rejects
     * any title starting with a letter on PHP 8.
     */
    public function testTitleLength(): void
    {
        $this->assertArrayNotHasKey('title', $this->violations($this->validTask()->setTitle('Zebra feed')));
        $this->assertArrayNotHasKey('title', $this->violations($this->validTask()->setTitle(str_repeat('x', 250))));
        $this->assertArrayHasKey('title', $this->violations($this->validTask()->setTitle(str_repeat('x', 251))));
        $this->assertArrayHasKey('title', $this->violations($this->validTask()->setTitle('')));
    }

    public function testCodeLength(): void
    {
        $this->assertArrayHasKey('code', $this->violations($this->validTask()->setCode(str_repeat('c', 251))));
    }

    public function testPeriodChoice(): void
    {
        $this->assertArrayHasKey('period', $this->violations($this->validTask()->setPeriod(123)));
        $this->assertArrayNotHasKey('period', $this->violations($this->validTask()->setPeriod(null)));
    }

    public function testImporterOptionsAreValidated(): void
    {
        $task = $this->validTask();
        $task->getImporterOptions()->setItemHashIdFields(null);

        $this->assertArrayHasKey('importerOptions.itemHashIdFieldsStr', $this->violations($task));
    }

    public function testLinkAndReaderGroup(): void
    {
        $task = $this->validTask()->setLink(null)->setReaderCode('unknown');

        $this->assertSame([], $this->violations($task), 'Default group does not check link and reader');
        $violations = $this->violations($task, [Task::VALIDATION_GROUP_LINK_AND_PARSER]);
        $this->assertArrayHasKey('link', $violations);
        $this->assertSame(['Undefined reader code'], $violations['readerCode']);
    }

    public function testItem(): void
    {
        $violations = $this->violations(new Item());
        foreach (['taskId', 'entryId', 'statusId', 'dataHash', 'configHash'] as $property) {
            $this->assertArrayHasKey($property, $violations);
        }
        $this->assertArrayHasKey('data', $this->violations((new Item())->setData([])), 'data must not be empty');

        $item = (new Item())->setTaskId(1)->setEntryId('e')->setStatusId(Item::STATUS_CREATED)->setConfigHash('h')->setData(['a' => 1]);
        $this->assertSame([], $this->violations($item));
    }

    public function testCsvReaderOptions(): void
    {
        $this->assertSame([], $this->violations(new CsvReaderOptions()));
    }
}
