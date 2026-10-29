<?php

namespace Dmytrof\ImportBundle\Tests\Unit\Model;

use Dmytrof\ImportBundle\Model\ImportStatistics;
use PHPUnit\Framework\TestCase;

class ImportStatisticsTest extends TestCase
{
    public function testIncrements(): void
    {
        $stats = (new ImportStatistics())
            ->setAll(10)
            ->incrementSkipped()
            ->incrementScheduled(2)
            ->incrementDuplicates()
            ->incrementCreated(3)
            ->incrementUpdated()
            ->incrementDeleted()
            ->incrementErrors(2)
        ;

        $this->assertSame(10, $stats->getAll());
        $this->assertSame(1, $stats->getSkipped());
        $this->assertSame(2, $stats->getScheduled());
        $this->assertSame(1, $stats->getDuplicates());
        $this->assertSame(3, $stats->getCreated());
        $this->assertSame(1, $stats->getUpdated());
        $this->assertSame(1, $stats->getDeleted());
        $this->assertSame(2, $stats->getErrors());
    }

    public function testIncrementAllFlag(): void
    {
        $stats = (new ImportStatistics())
            ->incrementCreated(2, true)
            ->incrementErrors(1, true)
            ->incrementSkipped(1)
        ;

        $this->assertSame(3, $stats->getAll());
    }

    public function testReset(): void
    {
        $stats = (new ImportStatistics())->setAll(5)->incrementCreated()->reset();

        $this->assertSame(0, $stats->getAll());
        $this->assertSame(0, $stats->getCreated());
    }

    public function testArrayConversion(): void
    {
        $stats = (new ImportStatistics())->setAll(4)->incrementCreated(3)->incrementErrors();
        $restored = (new ImportStatistics())->fromArray($stats->toArray());

        $this->assertSame($stats->toArray(), $restored->toArray());
        $this->assertSame(3, $restored->getCreated());
    }
}
