<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\UI\Integration;

use srag\Plugins\Opencast\Container\Container;
use ILIAS\DI\UIServices;
use srag\Plugins\Opencast\UI\Integration\Event\EventSettingsValueResolver;
use srag\Plugins\Opencast\UI\Integration\Event\EventActionTargetResolver;
use srag\Plugins\Opencast\UI\Integration\Series\SeriesActionTargetResolver;
use srag\Plugins\Opencast\UI\Integration\Series\NullSeriesActionResolver;
use srag\Plugins\Opencast\UI\Integration\Event\NullEventActionResolver;
use srag\Plugins\Opencast\UI\Integration\Event\NullEventSettingsResolver;

/**
 * @author Fabian Schmid <fabian@sr.solutions>
 * @internal
 */
class IntegrationBuilder
{
    public function __construct(private UIServices $services)
    {
    }

    public function main(Container $container): Integration
    {
        return new Integration(
            $container,
            $this->services->factory()
        );
    }

    public function external(
        \ilPlugin $external_plugin,
        Container $container,
        ?EventSettingsValueResolver $event_settings_value_resolver = null,
        ?EventActionTargetResolver $event_action_target_resolver = null,
        ?SeriesActionTargetResolver $series_action_target_resolver = null
    ): Integration {
        $event_settings_value_resolver ??= new NullEventSettingsResolver();
        $event_action_target_resolver ??= new NullEventActionResolver();
        $series_action_target_resolver ??= new NullSeriesActionResolver();

        return new Integration(
            $container,
            $this->services->factory(),
            $event_settings_value_resolver,
            $event_action_target_resolver,
            $series_action_target_resolver
        );
    }
}
