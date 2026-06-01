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

    public function formatDate(string|\DateTimeInterface $date): string
    {
        if (is_string($date)) {
            $date = new \DateTimeImmutable($date);
        }
        return $date->setTimezone($this->userTimeZone())->format('d. M Y, H:i');
    }

    /**
     * The timezone the current user expects dates to be displayed in. Opencast delivers timestamps in
     * UTC, so they must be converted before display (otherwise scheduled events show the UTC time). See #499.
     */
    public function userTimeZone(): \DateTimeZone
    {
        if (!isset($this->container) || !$this->container instanceof Container) {
            return new \DateTimeZone('UTC');
        }
        try {
            return new \DateTimeZone($this->container->ilias()->user()->getTimeZone() ?: 'UTC');
        } catch (\Throwable) {
            return new \DateTimeZone('UTC');
        }
    }

}
