<?php

namespace Dmytrof\ImportBundle\Tests\Unit\Form\DataTransformer;

use Dmytrof\ImportBundle\Form\DataTransformer\{ValueToDateTimeTransformer, ValueToIntegerTransformer, ValueToStringTransformer};
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;

class ValueTransformersTest extends TestCase
{
    public function testIntegerTransform(): void
    {
        $transformer = new ValueToIntegerTransformer([]);

        $this->assertSame(5, $transformer->transform('5'));
        $this->assertSame([1, 2], $transformer->transform(['1', '2']));
        $this->assertSame([3], $transformer->transform(new ArrayCollection(['3'])));
        $this->assertNull($transformer->transform(null));
    }

    public function testIntegerReverseTransform(): void
    {
        $this->assertSame(7, (new ValueToIntegerTransformer([]))->reverseTransform('7'));
        $this->assertNull((new ValueToIntegerTransformer([]))->reverseTransform(null));

        $collection = (new ValueToIntegerTransformer(['multiple' => true]))->reverseTransform(['1', '2']);
        $this->assertInstanceOf(ArrayCollection::class, $collection);
        $this->assertSame([1, 2], $collection->toArray());

        $this->assertSame([4], (new ValueToIntegerTransformer(['multiple' => true, 'as_array' => true]))->reverseTransform('4'));
    }

    public function testStringTransformer(): void
    {
        $transformer = new ValueToStringTransformer(['multiple' => true, 'as_array' => true]);

        $this->assertSame('12', (new ValueToStringTransformer([]))->transform(12));
        $this->assertSame(['1', '2'], $transformer->reverseTransform([1, 2]));
    }

    public function testDateTimeTransform(): void
    {
        $transformer = new ValueToDateTimeTransformer();

        $this->assertSame('2026-10-28T12:00:00+00:00', $transformer->transform(new \DateTimeImmutable('2026-10-28 12:00:00', new \DateTimeZone('UTC'))));
        $this->assertNull($transformer->transform('2026-10-28'));
    }

    public function testDateTimeReverseTransform(): void
    {
        $transformer = new ValueToDateTimeTransformer();
        $defaultTimezone = (new \DateTime())->getTimezone()->getName();

        $date = $transformer->reverseTransform('2026-10-28T12:00:00+00:00');
        $this->assertInstanceOf(\DateTime::class, $date);
        $this->assertSame($defaultTimezone, $date->getTimezone()->getName());
        $this->assertSame(strtotime('2026-10-28T12:00:00+00:00'), $date->getTimestamp());

        $this->assertNull($transformer->reverseTransform(''));
        $this->assertNull($transformer->reverseTransform(null));
        $this->assertNull($transformer->reverseTransform('not a date'));
    }

    public function testDateTimeReverseTransformConvertsImmutableTimezone(): void
    {
        $previous = date_default_timezone_get();
        date_default_timezone_set('Europe/Kyiv');
        try {
            $date = (new ValueToDateTimeTransformer())->reverseTransform(new \DateTimeImmutable('2026-10-28 12:00:00', new \DateTimeZone('UTC')));

            $this->assertSame('Europe/Kyiv', $date->getTimezone()->getName());
            $this->assertSame('2026-10-28 14:00', $date->format('Y-m-d H:i'));
        } finally {
            date_default_timezone_set($previous);
        }
    }
}
