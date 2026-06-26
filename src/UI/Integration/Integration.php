<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\UI\Integration;

use ILIAS\UI\Factory;
use srag\Plugins\Opencast\Container\Container;
use srag\Plugins\Opencast\Views\Series\SeriesActionResolver;
use srag\Plugins\Opencast\Views\Series\EventActionResolver;
use srag\Plugins\Opencast\Views\Series\EventSettingsResolver;
use srag\Plugins\Opencast\UI\Integration\Event\EventSettingsValueResolver;
use srag\Plugins\Opencast\UI\Integration\Event\EventActionTargetResolver;
use srag\Plugins\Opencast\UI\Integration\Series\SeriesActionTargetResolver;
use srag\Plugins\Opencast\State\SessionSettingsStore;

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
        Factory $factory,
        ?EventSettingsValueResolver $event_settings_value_resolver = null,
        ?EventActionTargetResolver $event_action_target_resolver = null,
        ?SeriesActionTargetResolver $series_action_target_resolver = null
    ) {
        $settings_resolver = $event_settings_value_resolver ?? new EventSettingsResolver(
            $container->objectSettings()
        );
        $this->events = new Events(
            $factory,
            $container,
            $event_action_target_resolver ?? new EventActionResolver(
                $container->translator(),
                $container->ilias()->http(),
                $container->ilias()->ctrl()
            ),
            $settings_resolver
        );
        $this->my_events = new MyEvents(
            $factory,
            $container,
            $this->events
        );
        $this->series = new Series(
            $container,
            $this->events,
            $series_action_target_resolver ?? new SeriesActionResolver(
                $container->translator(),
                $container->ilias()->http(),
                $container->ilias()->ctrl(),
                new SessionSettingsStore()
            ),
            $settings_resolver
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
