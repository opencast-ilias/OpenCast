<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\UI\Integration\Event;

use srag\Plugins\Opencast\UI\Integration\Action;

/**
 * @author Fabian Schmid <fabian@sr.solutions>
 * @internal
 */
class NullEventActionResolver implements EventActionTargetResolver
{
    public function resolve(
        EventActionTarget $target,
        ?EventActionParameters $parameter = null,
        ?EventSettingsValueResolver $settings = null
    ): ?Action {
        return null;
    }

    public function resolveBestForEventStatus(string $status, EventActionParameters $parameter): ?Action
    {
        return null;
    }

    public function supports(
        EventActionTarget $target,
        EventActionParameters $parameters,
        ?EventSettingsValueResolver $settings = null
    ): bool {
        return false;
    }

    public function resolveParameter(EventActionParameter $parameter): mixed
    {
        return null;
    }

}
