<?php

namespace Dmytrof\ImportBundle\Tests\Unit\Model;

use Dmytrof\ImportBundle\Model\ImportFormData;
use PHPUnit\Framework\TestCase;

class ImportFormDataTest extends TestCase
{
    public function testArrayAccess(): void
    {
        $data = new ImportFormData(['title' => 'Apple']);

        $this->assertTrue(isset($data['title']));
        $this->assertSame('Apple', $data['title']);
        $this->assertNull($data['missing']);

        $data['price'] = 1.5;
        $this->assertSame(1.5, $data['price']);

        unset($data['title']);
        $this->assertFalse(isset($data['title']));
        $this->assertSame(['price' => 1.5], $data->getData());
    }

    public function testRemoveEmptyProperties(): void
    {
        $data = new ImportFormData([
            'title' => 'Apple',
            'empty' => '',
            'null' => null,
            'zero' => 0,
            'address' => ['city' => '', 'street' => null],
            'tags' => ['a', ''],
        ]);

        $this->assertSame([
            'title' => 'Apple',
            'zero' => 0,
            'tags' => ['a'],
        ], $data->getData(true));
        $this->assertArrayHasKey('empty', $data->getData());
    }

    public function testOptions(): void
    {
        $data = new ImportFormData([]);

        $this->assertFalse($data->hasOption('locale'));
        $this->assertNull($data->getOption('locale'));

        $data->setOption('locale', 'uk');
        $this->assertTrue($data->hasOption('locale'));
        $this->assertSame('uk', $data->getOption('locale'));
    }
}
