<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\UI\Integration;

use srag\Plugins\Opencast\Model\Event\Event;
use srag\Plugins\Opencast\Container\Container;

/**
 * @author Fabian Schmid <fabian@sr.solutions>
 * @internal
 */
trait Commons
{
    private array $series_name_cache = [];

    protected function getSeriesName(Event $event): string
    {
        $series_id = $event->getSeries();
        if (isset($this->series_name_cache[$series_id])) {
            return $this->series_name_cache[$series_id];
        }

        $series_name = $this->series_repository->find($series_id)->getMetadata()->getField('title')->getValue();

        return $this->series_name_cache[$series_id] = $series_name;
    }

    /**
     * @throws \LogicException if the using class does not have a container property.
     */
    private function translate(string $key): string
    {
        if (!isset($this->container) || !$this->container instanceof Container) {
            throw new \LogicException(
                "The using class must have a 'container' property of" . Container::class . " type."
            );
        }
        return $this->container->translator()->translate($key);
    }

}
