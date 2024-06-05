<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\UI\Integration;

use ILIAS\UI\Factory;
use srag\Plugins\Opencast\Container\Container;
use srag\Plugins\Opencast\Util\Locale\Translator;
use srag\Plugins\Opencast\Container\Init;

/**
 * @author Fabian Schmid <fabian@sr.solutions>
 * @internal
 */
class Integration
{
    private MyEvents $my_events;

    public function __construct(
        Container $container,
        Factory $factory
    ) {
        $this->my_events = new MyEvents($factory, $container);
    }

    public function mine(): MyEvents
    {
        return $this->my_events;
    }
}
