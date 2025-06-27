<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\UI\Integration;

use ILIAS\UI\Factory;
use srag\Plugins\Opencast\Container\Container;

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

    public function __construct(
        Container $container,
        Factory $factory
    ) {
        $this->events = new Events(
            $factory,
            $container
        );
        $this->my_events = new MyEvents(
            $factory,
            $container,
            $this->events
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
}
