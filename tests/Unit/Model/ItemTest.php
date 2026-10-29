<?php

namespace Dmytrof\ImportBundle\Tests\Unit\Model;

use Dmytrof\ImportBundle\Exception\ItemException;
use Dmytrof\ImportBundle\Model\Item;
use PHPUnit\Framework\TestCase;

class ItemTest extends TestCase
{
    public function testGenerateIdUsesTaskEntryAndShortDataHash(): void
    {
        $this->assertSame('5_entry_abcdef12', Item::generateId(5, 'entry', 'abcdef1234567890'));
    }

    public function testGenerateIdAcceptsNullDataHash(): void
    {
        $this->assertSame('5_entry_', Item::generateId(5, 'entry', null));
    }

    public function testGenerateDataHash(): void
    {
        $data = ['title' => 'Apple', 'price' => 1.5];

        $this->assertSame(sha1(json_encode($data)), Item::generateDataHash($data));
        $this->assertNull(Item::generateDataHash(null));
    }

    public function testSettersRegenerateId(): void
    {
        $data = ['sku' => 'A1'];
        $item = (new Item())
            ->setTaskId(3)
            ->setEntryId('e1')
            ->setData($data)
        ;

        $this->assertSame(Item::generateDataHash($data), $item->getDataHash());
        $this->assertSame(Item::generateId(3, 'e1', Item::generateDataHash($data)), $item->getId());
        $this->assertSame('A1', $item->getDataValue('sku'));
        $this->assertSame('default', $item->getDataValue('missing', 'default'));
    }

    public function testIsNewWithoutDataHash(): void
    {
        $item = (new Item())->setTaskId(1)->setEntryId('e');
        $this->assertTrue($item->isNew());

        $item->setData(['a' => 1]);
        $this->assertFalse($item->isNew());
    }

    public function testErrors(): void
    {
        $item = new Item();
        $this->assertNull($item->getErrors());

        $item->setErrors(['title' => ['Required']]);
        $this->assertSame(['title' => ['Required']], $item->getErrors());
        $this->assertSame('{"title":["Required"]}', $item->getErrorsStr());

        $item->setErrors([]);
        $this->assertNull($item->getErrors());
    }

    public function testChangingStatusResetsErrors(): void
    {
        $item = (new Item())->setErrors(['oops']);
        $item->setStatusId(Item::STATUS_CREATED);

        $this->assertNull($item->getErrors());
    }

    public function testStatuses(): void
    {
        $item = new Item();
        $this->assertSame('Undefined', $item->getStatusTitle());

        $item->setStatusId(Item::STATUS_ERROR);
        $this->assertTrue($item->isStatusError());
        $this->assertFalse($item->isStatusDataError());
        $this->assertSame('label.import_item.statuses.error', $item->getStatusTitle());

        $item->setStatusId(Item::STATUS_DATA_ERROR);
        $this->assertTrue($item->isStatusDataError());

        $this->assertSame(array_keys(Item::STATUSES), Item::getStatuses());
        $this->assertSame(Item::STATUSES, Item::getStatusesTitles());
    }

    public function testGetTaskWithoutTaskManagerThrows(): void
    {
        $this->expectException(ItemException::class);
        (new Item())->setTaskId(1)->getTask();
    }
}
