<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\UI\Integration;

use ILIAS\UI\Factory;
use srag\Plugins\Opencast\Container\Container;
use srag\Plugins\Opencast\Views\Series\SeriesActionResolver;
use srag\Plugins\Opencast\Views\Series\EventActionResolver;

/**
 * @author Fabian Schmid <fabian@sr.solutions>
 * @internal
 */
class Integration
{
    /**
     * @readonly
     */
    private MyEvents $my_events;
    /**
     * @readonly
     */
    private Events $events;
    /**
     * @readonly
     */
    private Series $series;

    public function __construct(
        Container $container,
        Factory $factory
    ) {
        $this->events = new Events(
            $factory,
            $container,
            new EventActionResolver(
                $container->translator(),
                $container->ilias()->http(),
                $container->ilias()->ctrl()
            )
        );
        $this->my_events = new MyEvents(
            $factory,
            $container,
            $this->events
        );
        $this->series = new Series(
            $container,
            $this->events,
            new SeriesActionResolver(
                $container->translator(),
                $container->ilias()->http(),
                $container->ilias()->ctrl()
            )
        );
    }

    public function mine(): MyEvents
    {
        return $this->my_events;
    }

    public function events(): Events
    {
        return $this->events;
    }

    public function series(): Series
    {
        return $this->series;
    }
}
