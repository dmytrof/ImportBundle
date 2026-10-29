<?php

/*
 * This file is part of the DmytrofImportBundle package.
 *
 * (c) Dmytro Feshchenko <dmytro.feshchenko@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dmytrof\ImportBundle\Reader\Options;

use Dmytrof\ModelsManagementBundle\Model\Traits\ArrayConvertibleModelTrait;

abstract class AbstractReaderOptions implements ReaderOptionsInterface
{
    use ArrayConvertibleModelTrait;

    protected ?\SplObjectStorage $observers = null;

    /**
     * @return \SplObjectStorage
     */
    public function getObservers(): \SplObjectStorage
    {
        if (is_null($this->observers)) {
            $this->observers = new \SplObjectStorage();
        }
        return $this->observers;
    }

    /**
     * {@inheritdoc}
     */
    public function attach(\SplObserver $observer): void
    {
        $this->getObservers()->attach($observer);
    }

    /**
     * {@inheritdoc}
     */
    public function detach(\SplObserver $observer): void
    {
        $this->getObservers()->detach($observer);
    }

    /**
     * {@inheritdoc}
     */
    public function notify(): void
    {
        foreach ($this->getObservers() as $observer) {
            $observer->update($this);
        }
    }
}